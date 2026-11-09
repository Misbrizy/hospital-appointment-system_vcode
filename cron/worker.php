<?php
/**
 * Long-running alternative to cron: keeps this console window open and checks for due reminders every
 * 30 seconds. Handy on a Windows/XAMPP machine when you don't want to set up Task Scheduler.
 *
 *   C:\xampp\php\php.exe cron\worker.php          (Ctrl+C to stop)
 */
require dirname(__DIR__) . '/app/bootstrap.php';
set_time_limit(0);
echo "Reminder worker started. Checking every 30 seconds. Press Ctrl+C to stop.\n";
while (true) {
    try {
        $r = reminders_run_due('worker');
        if (!$r['skipped'] && ($r['due'] || $r['recovered'])) {
            echo date('H:i:s') . sprintf(" due=%d sent=%d retry-later=%d failed=%d cancelled=%d\n", $r['due'], $r['sent'], $r['retry'], $r['failed'], $r['cancelled']);
        }
    } catch (Throwable $e) {
        echo date('H:i:s') . ' error: ' . $e->getMessage() . "\n"; // e.g. MySQL briefly unavailable — keep going
    }
    sleep(30);
}
