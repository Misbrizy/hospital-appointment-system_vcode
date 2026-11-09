<?php
/**
 * Creates the reminders that SHOULD exist for upcoming appointments but were never queued (for example the
 * "today you have an appointment" reminder, when its template was missing at booking time).
 *
 *   C:\xampp\php\php.exe bin\repair_reminders.php --dry-run    (list what would be created)
 *   C:\xampp\php\php.exe bin\repair_reminders.php              (create them)
 *
 * It only adds what is missing: a reminder that already exists in ANY state (pending, sent, failed, cancelled)
 * is never queued again, so nobody is messaged twice.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Run this from the command line: php bin/repair_reminders.php\n"); }
require dirname(__DIR__) . '/app/bootstrap.php';

$dry = in_array('--dry-run', $argv, true);
foreach (rows('SELECT id FROM hospitals') as $h) {
    ensure_default_templates((int) $h['id']);
    ensure_default_reminder_rules((int) $h['id']);
}
$todo = reminders_find_missing();
if (!$todo) { echo "Nothing to repair - every upcoming appointment already has its reminders.\n"; exit(0); }

$made = 0;
foreach ($todo as $m) {
    echo ($dry ? '[dry-run] would queue ' : 'queueing ') . "{$m['channel']} reminder '{$m['rule_label']}' for appointment {$m['appt_no']} ({$m['appt_date']} {$m['appt_time']}) at {$m['scheduled_at']}\n";
    if ($dry) { continue; }
    $patient = row('SELECT * FROM patients WHERE id=?', [$m['patient_id']]);
    $hospital = row('SELECT name FROM hospitals WHERE id=?', [$m['hospital_id']]);
    $dest = ['sms' => $patient['phone'] ?? '', 'email' => $patient['email'] ?? '', 'whatsapp' => $patient['whatsapp_phone'] ?? ''][$m['channel']];
    $id = sms_queue((int) $m['hospital_id'], (int) $m['patient_id'], (int) $m['appointment_id'], 'reminder', (string) $dest, $m['template_code'], [
        'patient_name' => full_name($patient), 'hospital_name' => $hospital['name'] ?? '',
        'appointment_date' => fmt_date($m['appt_date']), 'appointment_time' => fmt_time($m['appt_time']),
    ], (string) $patient['preferred_language'], $m['scheduled_at'], $m['channel'], (int) $m['rule_id']);
    if ($id) { $made++; }
}
echo $dry ? "\nDry run only - nothing changed.\n" : "\nQueued $made reminder(s). The scheduler will deliver them when they come due.\n";
