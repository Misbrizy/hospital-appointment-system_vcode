<?php
/**
 * Delivers every reminder / message that has come due (SMS, Email and WhatsApp), retries temporary
 * failures, and records each attempt. Run every minute:
 *
 *   Linux cron:   * * * * * php /path/to/hospital-appointment/cron/send_sms.php >> /path/to/hospital-appointment/storage/logs/cron.log 2>&1
 *   Windows/XAMPP: right-click bin\install-windows-task.bat > Run as administrator, once (creates a Task Scheduler job that runs this every minute, also on battery)
 *
 * Safe to run alongside the web fallback or a second copy: a database lock and atomic claiming mean each
 * message is sent once.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

$r = reminders_run_due('cron');
if ($r['skipped']) {
    echo date('Y-m-d H:i:s') . " another scheduler run is in progress — skipped\n";
    exit(0);
}
if (!$r['due'] && !$r['recovered']) {
    exit(0); // nothing to do this minute - keep cron.log short (the heartbeat in storage/scheduler.json is still updated)
}
echo date('Y-m-d H:i:s') . sprintf(" due=%d sent=%d retry-later=%d failed=%d cancelled=%d recovered=%d (%.2fs)\n",
    $r['due'], $r['sent'], $r['retry'], $r['failed'], $r['cancelled'], $r['recovered'], $r['seconds']);
