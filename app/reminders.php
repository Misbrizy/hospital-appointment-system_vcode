<?php
declare(strict_types=1);

/**
 * Reminder delivery engine.
 *
 * WHY THIS EXISTS: reminders are queued as rows in sms_logs with a future scheduled_at. Nothing in the web
 * app sends them — a scheduler has to wake up regularly and deliver whatever has come due. Originally that
 * was only cron/send_sms.php, which needs a cron job; XAMPP on Windows has none, so reminders stayed
 * "Pending" forever while the booking confirmation (sent immediately) worked.
 *
 * Ways this engine now gets run (any one is enough; more than one at once is safe):
 *   1. Windows Task Scheduler / cron  -> php cron/send_sms.php           (recommended, runs even when nobody is online)
 *   2. A console worker               -> php cron/worker.php              (loops every 30 s)
 *   3. Web fallback                   -> every page view pings cron_tick.php in the background when the
 *                                        scheduler has been quiet for a minute (config: scheduler_web_trigger)
 *
 * Guarantees:
 *   - a due message is claimed with one atomic UPDATE, so two runners can never send the same message twice
 *   - every attempt is recorded in sms_attempts (time, outcome, provider id, error text)
 *   - temporary failures are retried with back-off; permanent failures stop immediately with a readable reason
 *   - before sending, the patient's CURRENT consent and contact details and the appointment's CURRENT state are
 *     re-checked, so nobody is messaged after opting out, after cancelling, or at an outdated number
 */

/* ------------------------------------------------------------------------------------------------
 * Delivery of one message
 * ---------------------------------------------------------------------------------------------- */

/** Kinds that are only meaningful while the appointment is still upcoming. */
function reminders_kind_needs_open_appointment(string $kind): bool
{
    return in_array($kind, ['reminder', 'confirmation', 'reschedule_approved'], true);
}

function reminders_finish(int $id, string $status, ?string $error, array $extra = []): void
{
    update('sms_logs', array_merge([
        'status' => $status, 'error' => $error !== null ? mb_substr($error, 0, 255) : null,
        'sending_started_at' => null,
    ], $extra), 'id=?', [$id]);
}

/**
 * Sends one queued row. Safe to call from anywhere, any number of times: it only acts on a row that is
 * pending, due, and not waiting for a retry slot, and it claims the row atomically before doing any I/O.
 */
function reminders_deliver(int $id): void
{
    $now = now();
    $claimed = q(
        "UPDATE sms_logs SET status='sending', sending_started_at=?, last_attempt_at=?, attempts=attempts+1
          WHERE id=? AND status='pending' AND scheduled_at <= ? AND (next_attempt_at IS NULL OR next_attempt_at <= ?)",
        [$now, $now, $id, $now, $now]
    )->rowCount();
    if ($claimed !== 1) {
        return; // someone else has it, or it is not due yet
    }
    $r = row('SELECT * FROM sms_logs WHERE id=?', [$id]);
    if (!$r) {
        return;
    }
    $attempt = (int) $r['attempts'];
    $channel = $r['channel'] ?: 'sms';

    // ---- 1. Pre-flight: is this message still correct to send? ------------------------------------
    $patient = null;
    if ($r['patient_id']) {
        $patient = row('SELECT * FROM patients WHERE id=? AND hospital_id=? AND deleted_at IS NULL', [$r['patient_id'], $r['hospital_id']]);
        if (!$patient) {
            reminders_finish($id, 'cancelled', 'Patient record is no longer available');
            return;
        }
        $optCol = ['sms' => 'sms_opt_in', 'email' => 'email_opt_in', 'whatsapp' => 'whatsapp_opt_in'][$channel] ?? 'sms_opt_in';
        if (empty($patient[$optCol])) {
            reminders_finish($id, 'cancelled', 'Patient has opted out of ' . $channel . ' messages');
            return;
        }
        // Always use the patient's CURRENT contact for this channel (they may have corrected it since queueing).
        $current = $channel === 'email'
            ? (filter_var((string) $patient['email'], FILTER_VALIDATE_EMAIL) ? (string) $patient['email'] : null)
            : normalize_phone($channel === 'whatsapp' ? ($patient['whatsapp_phone'] ?? null) : ($patient['phone'] ?? null));
        if ($current === null) {
            reminders_finish($id, 'failed', 'Patient has no valid ' . $channel . ' contact on file', ['next_attempt_at' => null]);
            record_attempt($id, $attempt, $channel, null, 'failed', null, 'Patient has no valid ' . $channel . ' contact on file', 0);
            return;
        }
        if ($current !== $r['to_phone']) {
            update('sms_logs', ['to_phone' => $current], 'id=?', [$id]);
            $r['to_phone'] = $current;
        }
    }
    if ($r['appointment_id']) {
        $appt = row('SELECT id, patient_id, status, appt_date, appt_time FROM appointments WHERE id=? AND hospital_id=?', [$r['appointment_id'], $r['hospital_id']]);
        if (!$appt || ($r['patient_id'] && (int) $appt['patient_id'] !== (int) $r['patient_id'])) {
            reminders_finish($id, 'failed', 'Appointment does not belong to this patient — not sent');
            record_attempt($id, $attempt, $channel, null, 'failed', null, 'Appointment/patient mismatch — not sent', 0);
            return;
        }
        if (reminders_kind_needs_open_appointment((string) $r['kind'])) {
            if (!in_array($appt['status'], ['scheduled', 'confirmed', 'checked_in'], true)) {
                reminders_finish($id, 'cancelled', 'Appointment is ' . str_replace('_', ' ', $appt['status']) . ' — message no longer needed');
                return;
            }
            if ($r['kind'] === 'reminder' && strtotime($appt['appt_date'] . ' ' . $appt['appt_time']) < time()) {
                reminders_finish($id, 'cancelled', 'Appointment time has already passed');
                return;
            }
        }
    }

    // ---- 2. Send ----------------------------------------------------------------------------------
    $t0 = microtime(true);
    $provider = $channel === 'email' ? 'smtp' : ($channel === 'whatsapp' ? 'twilio' : (string) cfg('sms_driver', 'log'));
    try {
        if ($channel === 'email') {
            [$ok, $providerId, $err, $retryable] = dispatch_email($r);
        } elseif ($channel === 'whatsapp') {
            [$ok, $providerId, $err, $retryable] = dispatch_whatsapp($r);
        } elseif ($provider === 'africastalking') {
            [$ok, $providerId, $err, $retryable] = sms_send_africastalking($r['to_phone'], $r['body']);
        } elseif (cfg('sms_allow_log_driver')) {
            error_log("[sms:log driver] to={$r['to_phone']} body={$r['body']}");
            [$ok, $providerId, $err, $retryable] = [true, 'log-' . $id, null, false];
        } else {
            [$ok, $providerId, $err, $retryable] = [false, null, 'SMS gateway is not configured (set SMS_DRIVER=africastalking with AT_USERNAME and AT_API_KEY)', false];
        }
    } catch (Throwable $e) {
        [$ok, $providerId, $err, $retryable] = [false, null, 'Unexpected error: ' . $e->getMessage(), true];
    }
    $ms = (int) round((microtime(true) - $t0) * 1000);

    // ---- 3. Record the outcome ----------------------------------------------------------------------
    $max = max(1, (int) cfg('reminder_max_attempts', 4));
    if ($ok) {
        record_attempt($id, $attempt, $channel, $provider, 'success', $providerId, null, $ms);
        reminders_finish($id, 'sent', null, ['sent_at' => now(), 'provider' => $provider, 'provider_message_id' => $providerId, 'next_attempt_at' => null]);
        return;
    }
    if ($retryable && $attempt < $max) {
        $steps = (array) cfg('reminder_retry_minutes', [5, 15, 60]);
        $wait = (int) ($steps[$attempt - 1] ?? end($steps) ?: 15);
        record_attempt($id, $attempt, $channel, $provider, 'retry', null, $err, $ms);
        reminders_finish($id, 'pending', $err, ['provider' => $provider, 'next_attempt_at' => date('Y-m-d H:i:s', time() + $wait * 60)]);
        return;
    }
    record_attempt($id, $attempt, $channel, $provider, 'failed', null, $err, $ms);
    reminders_finish($id, 'failed', $err ?: 'Delivery failed', ['provider' => $provider, 'next_attempt_at' => null]);
}

function record_attempt(int $logId, int $attemptNo, string $channel, ?string $provider, string $outcome, ?string $providerMsgId, ?string $error, int $ms): void
{
    try {
        insert('sms_attempts', [
            'sms_log_id' => $logId, 'attempt_no' => min(255, $attemptNo), 'channel' => $channel, 'provider' => $provider,
            'outcome' => $outcome, 'provider_message_id' => $providerMsgId,
            'error' => $error !== null ? mb_substr($error, 0, 255) : null, 'duration_ms' => $ms, 'attempted_at' => now(),
        ]);
    } catch (Throwable $e) {
        error_log('record_attempt failed: ' . $e->getMessage()); // history is best-effort; never block delivery bookkeeping
    }
}

/* ------------------------------------------------------------------------------------------------
 * The scheduler run
 * ---------------------------------------------------------------------------------------------- */

/**
 * Delivers everything that has come due. Only one run happens at a time (DB lock); a second caller returns
 * immediately with ['skipped' => true].
 *
 * @return array{skipped:bool, due:int, sent:int, retry:int, failed:int, cancelled:int, recovered:int, seconds:float}
 */
function reminders_run_due(string $source = 'cli', ?int $limit = null, int $timeBudgetSeconds = 50): array
{
    $out = ['skipped' => false, 'due' => 0, 'sent' => 0, 'retry' => 0, 'failed' => 0, 'cancelled' => 0, 'recovered' => 0, 'seconds' => 0.0];
    $t0 = microtime(true);
    if (!(int) val("SELECT GET_LOCK('haprs_reminder_scheduler', 0)")) {
        $out['skipped'] = true;
        return $out;
    }
    try {
        // A worker that died mid-send leaves rows in 'sending'. Put them back (or fail them if out of attempts).
        $stale = date('Y-m-d H:i:s', time() - (int) cfg('reminder_stale_minutes', 10) * 60);
        $max = max(1, (int) cfg('reminder_max_attempts', 4));
        $out['recovered'] += q("UPDATE sms_logs SET status='pending', sending_started_at=NULL, error='Interrupted while sending — will retry'
                                 WHERE status='sending' AND sending_started_at < ? AND attempts < ?", [$stale, $max])->rowCount();
        q("UPDATE sms_logs SET status='failed', sending_started_at=NULL, error='Interrupted while sending — no attempts left'
            WHERE status='sending' AND sending_started_at < ? AND attempts >= ?", [$stale, $max]);

        $limit = $limit ?? (int) cfg('reminder_batch_size', 100);
        $now = now();
        $due = rows("SELECT id FROM sms_logs WHERE status='pending' AND scheduled_at <= ? AND (next_attempt_at IS NULL OR next_attempt_at <= ?)
                      ORDER BY scheduled_at, id LIMIT " . max(1, $limit), [$now, $now]);
        $out['due'] = count($due);
        foreach ($due as $d) {
            if (microtime(true) - $t0 > $timeBudgetSeconds) { break; }
            $before = (int) val('SELECT attempts FROM sms_logs WHERE id=?', [$d['id']]);
            reminders_deliver((int) $d['id']);
            $after = row('SELECT status, attempts FROM sms_logs WHERE id=?', [$d['id']]);
            if (!$after || (int) $after['attempts'] === $before) { continue; } // not claimed by us
            match ($after['status']) {
                'sent', 'delivered' => $out['sent']++,
                'pending'           => $out['retry']++,
                'failed'            => $out['failed']++,
                'cancelled'         => $out['cancelled']++,
                default             => null,
            };
        }
    } finally {
        q("SELECT RELEASE_LOCK('haprs_reminder_scheduler')");
    }
    $out['seconds'] = round(microtime(true) - $t0, 2);
    scheduler_heartbeat($source, $out);
    return $out;
}

/* ------------------------------------------------------------------------------------------------
 * Scheduler health + the web-traffic fallback trigger
 * ---------------------------------------------------------------------------------------------- */

function scheduler_state_file(): string
{
    return APP_ROOT . '/storage/scheduler.json';
}

function scheduler_heartbeat(string $source, array $stats): void
{
    @file_put_contents(scheduler_state_file(), json_encode(['last_run' => time(), 'source' => $source, 'stats' => $stats]), LOCK_EX);
}

/** @return array{last_run:?int, age:?int, source:?string, healthy:bool, stats:array} */
function scheduler_status(): array
{
    $raw = @file_get_contents(scheduler_state_file());
    $d = $raw ? json_decode($raw, true) : null;
    if (!is_array($d) || empty($d['last_run'])) {
        return ['last_run' => null, 'age' => null, 'source' => null, 'healthy' => false, 'stats' => []];
    }
    $age = time() - (int) $d['last_run'];
    return ['last_run' => (int) $d['last_run'], 'age' => $age, 'source' => $d['source'] ?? null,
            'healthy' => $age <= max(180, 3 * (int) cfg('scheduler_interval', 60)), 'stats' => $d['stats'] ?? []];
}

function scheduler_token(): string
{
    return hash_hmac('sha256', 'scheduler-tick', app_key());
}

/**
 * Web fallback for installs with no cron / Task Scheduler: if nothing has run the scheduler recently, fire a
 * background request at cron_tick.php (the visitor's page is not delayed — we write the request and hang up).
 * It only works while someone is using the system; the Task Scheduler / cron route is what makes reminders go
 * out at 06:30 on a Sunday with nobody logged in.
 */
function scheduler_web_kick_if_due(): void
{
    if (PHP_SAPI === 'cli' || !cfg('scheduler_web_trigger')) {
        return;
    }
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === 'cron_tick.php') {
        return;
    }
    $interval = max(15, (int) cfg('scheduler_interval', 60));
    $st = scheduler_status();
    if ($st['age'] !== null && $st['age'] < $interval) {
        return;
    }
    $throttle = APP_ROOT . '/storage/scheduler.kick';
    if (is_file($throttle) && time() - (int) @filemtime($throttle) < 20) {
        return;
    }
    @touch($throttle);

    $https = is_https();
    $port = (int) ($_SERVER['SERVER_PORT'] ?? ($https ? 443 : 80));
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $path = rtrim(base_path(), '/') . '/cron_tick.php?token=' . scheduler_token();
    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $fp = @stream_socket_client(($https ? 'ssl://' : 'tcp://') . '127.0.0.1:' . $port, $errno, $errstr, 0.5, STREAM_CLIENT_CONNECT, $ctx);
    if ($fp) {
        @fwrite($fp, "GET $path HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n\r\n");
        @fclose($fp);
    }
}
