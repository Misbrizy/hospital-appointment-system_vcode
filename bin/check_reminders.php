<?php
/**
 * One-command health check for reminder delivery. Run it the same way the Task Scheduler runs the reminders:
 *
 *   C:\xampp\php\php.exe bin\check_reminders.php
 *   C:\xampp\php\php.exe bin\check_reminders.php --email you@gmail.com      (also sends a real test email)
 *
 * It answers, in plain words, "why did the confirmation email arrive but the reminders did not?" by checking
 * everything a reminder depends on: clocks/time zone, the scheduler heartbeat, templates, reminder rules,
 * mail settings, messages stuck in the queue, and (with --email) the SMTP login as seen from the command line.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Run this from the command line: php bin/check_reminders.php\n"); }
require dirname(__DIR__) . '/app/bootstrap.php';

$problems = 0;
$ok   = function (string $m) { echo "  [ OK ] $m\n"; };
$bad  = function (string $m) use (&$problems) { $problems++; echo "  [FAIL] $m\n"; };
$warn = function (string $m) { echo "  [WARN] $m\n"; };

echo "\n1. Clocks and time zone\n";
$phpNow = date('Y-m-d H:i:s');
$dbNow = (string) val('SELECT NOW()');
$diff = abs(strtotime($dbNow) - strtotime($phpNow));
echo "       PHP  : $phpNow (" . date_default_timezone_get() . ")\n       MySQL: $dbNow\n";
$diff <= 120 ? $ok('PHP and MySQL agree on the time') : $bad("PHP and MySQL differ by {$diff}s - reminders would fire at the wrong time");

echo "\n2. Scheduler (the thing that actually sends reminders)\n";
$st = scheduler_status();
if ($st['last_run'] === null) {
    $bad('The scheduler has NEVER run. Reminders only leave the queue when it runs: install the Windows task (bin\\install-windows-task.bat) or keep bin\\start-reminder-worker.bat open.');
} else {
    $age = (int) $st['age'];
    echo "       last run: " . date('Y-m-d H:i:s', (int) $st['last_run']) . " ({$age}s ago, started by '{$st['source']}')\n";
    if ($st['healthy']) {
        $ok('scheduler ran recently');
    } else {
        $bad("scheduler has been silent for " . round($age / 60) . " minutes - nothing is being delivered. (The web fallback only runs while somebody is using the website.)");
    }
    if ($st['source'] === 'web') {
        $warn("the last run came from the website fallback, not from Task Scheduler/cron/worker. Reminders will be late or missed when nobody is on the site (e.g. at 06:30).");
    }
}

echo "\n3. Hospitals: templates and reminder rules\n";
$have = [];
foreach (rows('SELECT hospital_id, code, language FROM sms_templates') as $t) { $have[$t['hospital_id']][$t['code'] . '|' . $t['language']] = true; }
foreach (rows('SELECT id, name FROM hospitals ORDER BY id') as $h) {
    $missing = [];
    foreach (default_templates() as [$code, $lang]) { if (empty($have[$h['id']][$code . '|' . $lang])) { $missing[] = "$code/$lang"; } }
    $rules = rows('SELECT * FROM reminder_rules WHERE hospital_id=? AND is_active=1 ORDER BY days_before DESC', [$h['id']]);
    echo "       #{$h['id']} {$h['name']}\n";
    $missing ? $bad('missing templates: ' . implode(', ', $missing) . '  -> run: php bin\\migrate.php') : $ok('all default templates present');
    if (!$rules) { $bad('no active reminder rules -> no reminders will ever be queued. Run php bin\\migrate.php or add rules under Reminder rules.'); }
    foreach ($rules as $r) {
        $exists = (int) val('SELECT COUNT(*) FROM sms_templates WHERE hospital_id=? AND code=?', [$h['id'], $r['template_code']]);
        $line = "rule '{$r['label']}' ({$r['days_before']} day(s) before, at " . substr((string) $r['send_time'], 0, 5) . ", template '{$r['template_code']}')";
        $exists ? $ok($line) : $bad($line . ' - template does not exist, so this reminder is never created');
    }
}

echo "\n4. Email settings (as seen from the command line, which is what the scheduler uses)\n";
$cfgOk = hospital_smtp_config((int) (val('SELECT MIN(id) FROM hospitals') ?: 0));
if (!$cfgOk) {
    $bad("email is not configured (mail_enabled/mail_username/mail_password in config/local.php, or Settings > Email)");
} else {
    $ok("SMTP {$cfgOk['host']}:{$cfgOk['port']} ({$cfgOk['encryption']}) as {$cfgOk['username']}");
    if (!extension_loaded('openssl') && $cfgOk['encryption'] !== 'none') { $bad('PHP "openssl" extension is not loaded in the CLI - STARTTLS to Gmail will fail from the scheduler'); }
    $i = array_search('--email', $argv, true);
    $to = $i !== false ? ($argv[$i + 1] ?? '') : '';
    if ($to !== '') {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $bad("'$to' is not a valid email address"); }
        else {
            [$sent, $err] = smtp_send($cfgOk, $to, '', cfg('app_name') . ' - scheduler test', "If you can read this, emails sent by the scheduler (command line) work.\nSent at $phpNow.");
            $sent ? $ok("test email sent to $to - check the inbox (and Spam)") : $bad("test email FAILED: $err");
        }
    } else {
        echo "       (add  --email you@gmail.com  to send a real test message)\n";
    }
}

echo "\n5. Messages waiting in the queue\n";
$counts = rows("SELECT status, channel, COUNT(*) n FROM sms_logs GROUP BY status, channel ORDER BY status, channel");
foreach ($counts as $c) { echo sprintf("       %-10s %-9s %d\n", $c['status'], $c['channel'], $c['n']); }
$overdue = (int) val("SELECT COUNT(*) FROM sms_logs WHERE status='pending' AND scheduled_at < ? AND (next_attempt_at IS NULL OR next_attempt_at < ?)", [date('Y-m-d H:i:s', time() - 300), now()]);
$overdue ? $bad("$overdue message(s) are overdue by more than 5 minutes - the scheduler is not delivering them") : $ok('nothing overdue');
foreach (rows("SELECT channel, error, COUNT(*) n FROM sms_logs WHERE status='failed' AND error IS NOT NULL GROUP BY channel, error ORDER BY n DESC LIMIT 5") as $f) {
    echo "       failed x{$f['n']} ({$f['channel']}): {$f['error']}\n";
}

echo "\n6. Upcoming appointments whose reminders were never queued\n";
$missingN = 0;
foreach (reminders_find_missing() as $m) { $missingN++; if ($missingN <= 10) { echo "       appointment {$m['appt_no']} ({$m['appt_date']}) - rule '{$m['rule_label']}' on {$m['channel']}\n"; } }
$missingN ? $bad("$missingN reminder(s) are missing -> run: php bin\\repair_reminders.php") : $ok('every upcoming appointment has all the reminders it should');

echo "\n" . ($problems ? "RESULT: $problems problem(s) found - fix the [FAIL] lines above.\n" : "RESULT: everything a reminder depends on looks healthy.\n");
exit($problems ? 1 : 0);
