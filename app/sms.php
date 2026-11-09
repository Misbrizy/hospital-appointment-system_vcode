<?php
declare(strict_types=1);

/**
 * Renders a template and enqueues a reminder row (status=pending) on one channel —
 * sms, email or whatsapp. Delivery itself is done by the reminder engine (app/reminders.php), which is
 * triggered by the scheduler (cron / Windows Task Scheduler / the built-in web fallback). This function
 * never blocks a web request on anything except the optional immediate send of "send now" messages.
 *
 * @param ?int $ruleId reminder_rules.id this row was produced by (null for confirmations etc.); used so the
 *                     same rule is never queued twice for the same appointment and channel.
 */
function sms_queue(int $hospitalId, ?int $patientId, ?int $appointmentId, string $kind, string $to, string $templateCode, array $vars, string $lang, ?string $scheduledAt = null, string $channel = 'sms', ?int $ruleId = null): ?int
{
    if ($channel === 'email') {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { return null; }
        $dest = $to;
    } else {
        $dest = normalize_phone($to);
        if ($dest === null) { return null; }
    }
    $tpl = sms_find_template($hospitalId, $templateCode, $lang);
    if (!$tpl) {
        // A hospital created by an older version (or one that deleted a template) may lack a default one.
        // Restore the defaults and look again before giving up.
        ensure_default_templates($hospitalId);
        $tpl = sms_find_template($hospitalId, $templateCode, $lang);
    }
    if (!$tpl) {
        // Last resort: a custom template code that does not exist. Never lose the message silently —
        // leave a visible FAILED row (shown under Message delivery) saying exactly what to fix.
        error_log("sms_queue: missing template $templateCode for hospital $hospitalId");
        $reason = "Template '$templateCode' does not exist for this hospital (SMS templates page) - message was not queued";
        try {
            insert('sms_logs', [
                'hospital_id' => $hospitalId, 'patient_id' => $patientId, 'appointment_id' => $appointmentId, 'reminder_rule_id' => $ruleId,
                'kind' => $kind, 'channel' => $channel, 'to_phone' => $dest, 'body' => '(no message text - template missing)',
                'status' => 'failed', 'error' => mb_substr($reason, 0, 255), 'scheduled_at' => $scheduledAt ?? now(), 'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            error_log('sms_queue: could not record missing-template failure: ' . $e->getMessage());
        }
        return null;
    }
    $when = $scheduledAt ?? now();

    // Never queue the same message twice. A reminder is identified by (appointment, rule, channel, day it is due);
    // anything else (confirmation, cancellation...) by (appointment, kind, channel) within the last two minutes,
    // which absorbs a double-clicked "Save" without blocking a genuine later message of the same kind.
    if ($appointmentId !== null) {
        if ($ruleId !== null) {
            $dup = val("SELECT id FROM sms_logs WHERE appointment_id=? AND reminder_rule_id=? AND channel=? AND DATE(scheduled_at)=? AND status IN ('pending','sending','sent','delivered') LIMIT 1",
                [$appointmentId, $ruleId, $channel, substr($when, 0, 10)]);
        } else {
            $dup = val("SELECT id FROM sms_logs WHERE appointment_id=? AND kind=? AND channel=? AND reminder_rule_id IS NULL AND created_at >= ? AND status IN ('pending','sending','sent','delivered') LIMIT 1",
                [$appointmentId, $kind, $channel, date('Y-m-d H:i:s', time() - 120)]);
        }
        if ($dup) { return null; }
    }

    $body = $tpl['body'];
    foreach ($vars as $k => $v) {
        $body = str_replace('{' . $k . '}', (string) $v, $body);
    }
    $id = insert('sms_logs', [
        'hospital_id'      => $hospitalId,
        'patient_id'       => $patientId,
        'appointment_id'   => $appointmentId,
        'reminder_rule_id' => $ruleId,
        'kind'             => $kind,
        'channel'          => $channel,
        'to_phone'         => $dest,
        'body'             => mb_substr($body, 0, 1200),
        'status'           => 'pending',
        'scheduled_at'     => $when,
        'created_at'       => now(),
    ]);
    if (cfg('sms_send_inline') && $when <= now()) {
        sms_dispatch_one($id);
    }
    return $id;
}

function sms_find_template(int $hospitalId, string $code, string $lang): ?array
{
    return row('SELECT body FROM sms_templates WHERE hospital_id=? AND code=? AND language=?', [$hospitalId, $code, $lang])
        ?? row('SELECT body FROM sms_templates WHERE hospital_id=? AND code=? ORDER BY language LIMIT 1', [$hospitalId, $code]);
}

/**
 * Queues the same message on every channel the patient has opted into (SMS, email, WhatsApp) — whichever of
 * their phone / email / whatsapp_phone they've given and enabled. A failure on one channel never blocks another.
 */
function reminder_queue_all_channels(int $hospitalId, array $patient, ?int $appointmentId, string $kind, string $templateCode, array $vars, string $lang, ?string $scheduledAt = null, ?int $ruleId = null): void
{
    $targets = [
        ['sms', !empty($patient['sms_opt_in']), $patient['phone'] ?? ''],
        ['email', !empty($patient['email_opt_in']), $patient['email'] ?? ''],
        ['whatsapp', !empty($patient['whatsapp_opt_in']), $patient['whatsapp_phone'] ?? ''],
    ];
    foreach ($targets as [$channel, $optedIn, $dest]) {
        if (!$optedIn || $dest === '' || $dest === null) { continue; }
        try {
            sms_queue($hospitalId, (int) $patient['id'], $appointmentId, $kind, (string) $dest, $templateCode, $vars, $lang, $scheduledAt, $channel, $ruleId);
        } catch (Throwable $e) {
            error_log("reminder_queue_all_channels[$channel]: " . $e->getMessage());
        }
    }
}

/**
 * Delivers one queued row now (claims it atomically, so it is safe to call from the web request, the
 * scheduler and a second scheduler at the same time — each message is sent at most once per attempt).
 * Kept under its old name because other modules call it; the work happens in reminders_deliver().
 */
function sms_dispatch_one(int $id): void
{
    reminders_deliver($id);
}

/** @return array{0:bool,1:?string,2:?string,3:bool} [ok, provider_message_id, error, retryable] */
function dispatch_email(array $r): array
{
    $smtpCfg = hospital_smtp_config((int) $r['hospital_id']);
    if (!$smtpCfg) {
        // Not configured is a real failure — never report a message as sent when nothing left the server.
        return [false, null, 'Email is not configured (Settings > Email, or MAIL_* in config/local.php)', false];
    }
    $patientName = '';
    if ($r['patient_id']) {
        $p = row('SELECT first_name, last_name FROM patients WHERE id=?', [$r['patient_id']]);
        $patientName = $p ? full_name($p) : '';
    }
    $subject = cfg('app_name') . ' — ' . email_subject_label($r);
    [$ok, $err, $temporary] = smtp_send($smtpCfg, $r['to_phone'], $patientName, $subject, $r['body']);
    return [$ok, $ok ? ('smtp-' . $r['id']) : null, $err, (bool) $temporary];
}

/** Human subject for an email row: tells a same-day reminder, a day-before reminder and a confirmation apart at a glance. */
function email_subject_label(array $r): string
{
    $kind = (string) $r['kind'];
    if ($kind === 'reminder') {
        $code = !empty($r['reminder_rule_id']) ? (string) val('SELECT template_code FROM reminder_rules WHERE id=?', [$r['reminder_rule_id']]) : '';
        return $code === 'reminder_today' ? 'Appointment TODAY' : 'Appointment reminder';
    }
    return match ($kind) {
        'confirmation' => 'Appointment confirmation',
        'reschedule_approved' => 'Appointment rescheduled',
        'cancelled' => 'Appointment cancelled',
        default => ucfirst(str_replace('_', ' ', $kind)),
    };
}

/** @return array{0:bool,1:?string,2:?string,3:bool} */
function dispatch_whatsapp(array $r): array
{
    $waCfg = hospital_whatsapp_config((int) $r['hospital_id']);
    if (!$waCfg) {
        return [false, null, 'WhatsApp is not configured (Settings > WhatsApp, or TWILIO_* in config/local.php)', false];
    }
    $to = normalize_phone($r['to_phone']) ?? $r['to_phone'];
    return whatsapp_send($waCfg, $to, $r['body']);
}

/**
 * Email settings for a hospital: the hospital's own SMTP settings (Settings page) if it saved any, otherwise the
 * server-wide MAIL_* configuration. Null if neither is usable.
 */
function hospital_smtp_config(int $hospitalId): ?array
{
    $host = setting($hospitalId, 'smtp_host');
    if ($host) {
        $pass = decrypt_field(setting($hospitalId, 'smtp_password'));
        $global = (string) cfg('mail_password', '');
        return [
            'host' => $host, 'port' => (int) setting($hospitalId, 'smtp_port', 587),
            'username' => (string) setting($hospitalId, 'smtp_username', cfg('mail_username', '')),
            'password' => $pass ?? $global,
            'encryption' => (string) setting($hospitalId, 'smtp_encryption', 'tls'),
            'from_email' => (string) (setting($hospitalId, 'smtp_from_email') ?: cfg('mail_from') ?: setting($hospitalId, 'smtp_username', '')),
            'from_name' => (string) setting($hospitalId, 'smtp_from_name', cfg('app_name')),
        ];
    }
    if (cfg('mail_enabled') && cfg('mail_host') && cfg('mail_username') !== '' && cfg('mail_password') !== '') {
        return [
            'host' => (string) cfg('mail_host'), 'port' => (int) cfg('mail_port', 587),
            'username' => (string) cfg('mail_username'), 'password' => (string) cfg('mail_password'),
            'encryption' => (string) cfg('mail_encryption', 'tls'),
            'from_email' => (string) (cfg('mail_from') ?: cfg('mail_username')),
            'from_name' => (string) cfg('mail_from_name', cfg('app_name')),
        ];
    }
    return null;
}

/** WhatsApp (Twilio) settings: the hospital's own, else the server-wide TWILIO_* values. Null if neither is complete. */
function hospital_whatsapp_config(int $hospitalId): ?array
{
    $sid = setting($hospitalId, 'wa_twilio_sid');
    if ($sid) {
        return [
            'twilio_sid' => $sid, 'twilio_token' => decrypt_field(setting($hospitalId, 'wa_twilio_token')) ?? '',
            'twilio_whatsapp_from' => (string) setting($hospitalId, 'wa_twilio_from', ''),
        ];
    }
    if (cfg('twilio_sid') && cfg('twilio_token') && cfg('twilio_whatsapp_from')) {
        return ['twilio_sid' => (string) cfg('twilio_sid'), 'twilio_token' => (string) cfg('twilio_token'), 'twilio_whatsapp_from' => (string) cfg('twilio_whatsapp_from')];
    }
    return null;
}

/** @return array{0:bool,1:?string,2:?string,3:bool} [ok, provider_message_id, error, retryable] */
function sms_send_africastalking(string $toPhoneE164, string $message): array
{
    $username = (string) cfg('at_username');
    $apiKey   = (string) cfg('at_api_key');
    if ($username === '' || $apiKey === '') {
        return [false, null, 'Africa\'s Talking is not configured (AT_USERNAME / AT_API_KEY)', false];
    }
    $fields = [
        'username' => $username,
        'to'       => $toPhoneE164,
        'message'  => $message,
        'bulkSMSMode' => 1,
    ];
    $senderId = (string) cfg('at_sender_id');
    if ($senderId !== '') {
        $fields['from'] = $senderId;
    }
    $host = (string) cfg('at_api_base') ?: (str_starts_with($username, 'sandbox') ? 'https://api.sandbox.africastalking.com' : 'https://api.africastalking.com');
    $ch = curl_init(rtrim($host, '/') . '/version1/messaging');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HTTPHEADER => ['apiKey: ' . $apiKey, 'Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno || $resp === false) {
        return [false, null, 'network error contacting SMS gateway', true];
    }
    $data = json_decode((string) $resp, true);
    $rcpt = $data['SMSMessageData']['Recipients'][0] ?? null;
    // 100 Processed, 101 Sent, 102 Queued are all "the gateway accepted it"
    if ($httpCode >= 200 && $httpCode < 300 && $rcpt && in_array((int) ($rcpt['statusCode'] ?? 0), [100, 101, 102], true)) {
        return [true, (string) ($rcpt['messageId'] ?? ''), null, false];
    }
    $status = (string) ($rcpt['status'] ?? ($data['SMSMessageData']['Message'] ?? ('HTTP ' . $httpCode)));
    // Gateway/internal errors (HTTP 5xx, AT codes 500/501) and throttling are temporary; invalid number, blocked, bad key are not.
    $retryable = $httpCode >= 500 || $httpCode === 429 || in_array((int) ($rcpt['statusCode'] ?? 0), [500, 501], true);
    return [false, null, $status, $retryable];
}

/** In-app notification, independent of SMS. */
function notify(int $userId, string $title, string $body = '', string $link = '', ?int $hospitalId = null, ?int $appointmentId = null): void
{
    insert('notifications', ['hospital_id' => $hospitalId, 'user_id' => $userId, 'title' => $title, 'body' => $body, 'link' => $link, 'appointment_id' => $appointmentId, 'created_at' => now()]);
}
