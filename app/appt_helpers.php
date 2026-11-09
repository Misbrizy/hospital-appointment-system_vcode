<?php
declare(strict_types=1);

/**
 * Queues reminders (SMS / email / WhatsApp, per the patient's opted-in channels) for an appointment, following the
 * hospital's reminder rules.
 *
 * @param ?array $ruleIds rules the person picked for THIS appointment. null or empty = every active rule of the hospital
 *                        (an appointment created by the patient portal, or one whose rule list was never stored,
 *                        must still get the hospital's standard reminders).
 */
function queue_appointment_reminders(array $appt, array $patient, int $hospitalId, ?array $ruleIds = null): void
{
    if (!$patient['sms_opt_in'] && empty($patient['email_opt_in']) && empty($patient['whatsapp_opt_in'])) {
        return;
    }
    if (in_array($appt['status'] ?? 'scheduled', ['cancelled', 'completed', 'missed'], true)) {
        return;
    }
    $apptAt = strtotime($appt['appt_date'] . ' ' . $appt['appt_time']);
    if ($apptAt === false || $apptAt < time()) {
        return; // nothing to remind about
    }
    $ruleIds = array_values(array_filter(array_map('intval', $ruleIds ?? []), fn($i) => $i > 0));
    $sql = 'SELECT * FROM reminder_rules WHERE hospital_id=? AND is_active=1';
    $params = [$hospitalId];
    if ($ruleIds) {
        $sql .= ' AND id IN (' . implode(',', array_fill(0, count($ruleIds), '?')) . ')';
        $params = array_merge($params, $ruleIds);
    }
    $rules = rows($sql . ' ORDER BY days_before DESC', $params);
    $hospital = row('SELECT name FROM hospitals WHERE id=?', [$hospitalId]);
    foreach ($rules as $r) {
        $scheduledAt = reminder_schedule_time($appt, $r);
        if ($scheduledAt === null) { continue; }
        reminder_queue_all_channels($hospitalId, $patient, (int) $appt['id'], 'reminder', $r['template_code'], [
            'patient_name' => full_name($patient), 'hospital_name' => $hospital['name'] ?? '',
            'appointment_date' => fmt_date($appt['appt_date']), 'appointment_time' => fmt_time($appt['appt_time']),
        ], $patient['preferred_language'], $scheduledAt, (int) $r['id']);
    }
}

/**
 * When a reminder rule should fire for an appointment, as 'Y-m-d H:i:s', or null if it should not be queued at all
 * (its day is already behind us, or the appointment is so close a "reminder" would only repeat the confirmation).
 */
function reminder_schedule_time(array $appt, array $rule): ?string
{
    $apptAt = strtotime($appt['appt_date'] . ' ' . $appt['appt_time']);
    if ($apptAt === false || $apptAt < time()) {
        return null;
    }
    $sendDate = date('Y-m-d', strtotime($appt['appt_date'] . ' -' . (int) $rule['days_before'] . ' days'));
    if ($sendDate < date('Y-m-d')) {
        return null; // that reminder's day is already behind us
    }
    $scheduledAt = $sendDate . ' ' . $rule['send_time'];
    if ($scheduledAt <= now()) {
        // The rule's time today has passed but the appointment is still ahead: send shortly (once).
        // If the appointment itself is within the hour there's no point sending a "reminder" on top of the confirmation.
        if ($apptAt - time() < 3600) {
            return null;
        }
        $scheduledAt = date('Y-m-d H:i:s', time() + 60);
    }
    return $scheduledAt;
}

/**
 * Upcoming, still-open appointments that are missing a reminder they should have, per the rules chosen for the
 * appointment and the channels the patient opted into. "Missing" means NO row at all for (appointment, rule, channel)
 * in any state, so a reminder that was sent, failed or cancelled is never re-created.
 *
 * @return list<array<string,mixed>>
 */
function reminders_find_missing(): array
{
    $out = [];
    $appts = rows("SELECT * FROM appointments WHERE status IN ('scheduled','confirmed') AND CONCAT(appt_date,' ',appt_time) > ? ORDER BY appt_date, appt_time", [now()]);
    foreach ($appts as $a) {
        $patient = row('SELECT * FROM patients WHERE id=? AND deleted_at IS NULL', [$a['patient_id']]);
        if (!$patient) { continue; }
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $a['reminder_rule_ids']))));
        if (!$ids) { continue; } // the booker deliberately ticked no reminders
        $rules = rows('SELECT * FROM reminder_rules WHERE hospital_id=? AND is_active=1 AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY days_before DESC', array_merge([$a['hospital_id']], $ids));
        foreach ($rules as $r) {
            $when = reminder_schedule_time($a, $r);
            if ($when === null) { continue; }
            foreach (['sms' => ['sms_opt_in', 'phone'], 'email' => ['email_opt_in', 'email'], 'whatsapp' => ['whatsapp_opt_in', 'whatsapp_phone']] as $channel => [$optCol, $contactCol]) {
                if (empty($patient[$optCol]) || trim((string) ($patient[$contactCol] ?? '')) === '') { continue; }
                $exists = (int) val('SELECT COUNT(*) FROM sms_logs WHERE appointment_id=? AND reminder_rule_id=? AND channel=?', [$a['id'], $r['id'], $channel]);
                if ($exists === 0) {
                    $out[] = ['appointment_id' => $a['id'], 'appt_no' => $a['appt_no'], 'appt_date' => $a['appt_date'], 'appt_time' => $a['appt_time'],
                        'hospital_id' => $a['hospital_id'], 'patient_id' => $a['patient_id'], 'rule_id' => $r['id'], 'rule_label' => $r['label'],
                        'template_code' => $r['template_code'], 'channel' => $channel, 'scheduled_at' => $when];
                }
            }
        }
    }
    return $out;
}

function cancel_pending_reminders(int $appointmentId): void
{
    update('sms_logs', ['status' => 'cancelled', 'error' => 'Appointment changed or cancelled', 'next_attempt_at' => null], "appointment_id=? AND status='pending'", [$appointmentId]);
}

function send_appointment_sms(string $kind, array $appt, array $patient, int $hospitalId): void
{
    if (!$patient['sms_opt_in'] && empty($patient['email_opt_in']) && empty($patient['whatsapp_opt_in'])) {
        return;
    }
    $hospital = row('SELECT name FROM hospitals WHERE id=?', [$hospitalId]);
    reminder_queue_all_channels($hospitalId, $patient, $appt['id'], $kind, $kind, [
        'patient_name' => full_name($patient), 'hospital_name' => $hospital['name'] ?? '',
        'appointment_date' => fmt_date($appt['appt_date']), 'appointment_time' => fmt_time($appt['appt_time']),
    ], $patient['preferred_language']);
}

function next_appt_no(int $hospitalId): string
{
    return 'AP-' . str_pad((string) next_seq($hospitalId, 'appt_seq'), 6, '0', STR_PAD_LEFT);
}
