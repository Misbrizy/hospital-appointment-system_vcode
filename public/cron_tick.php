<?php
/**
 * Background trigger for the reminder scheduler (the web fallback — see app/reminders.php).
 * Called automatically by the app itself, or by any external uptime/cron service:
 *   https://your-site/cron_tick.php?token=<value shown in Settings > Reminder scheduler>
 * The token is derived from APP_KEY, so only someone with the server config can compute it.
 */
require dirname(__DIR__) . '/app/bootstrap.php';
ignore_user_abort(true);
set_time_limit(120);
header('Content-Type: text/plain; charset=utf-8');

$token = (string) ($_GET['token'] ?? '');
if (!hash_equals(scheduler_token(), $token)) {
    http_response_code(403);
    exit("forbidden\n");
}
// Release the session lock immediately so this never blocks the visitor's own requests.
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

$r = reminders_run_due('web', null, 45);
echo $r['skipped'] ? "skipped (already running)\n" : sprintf("due=%d sent=%d retry=%d failed=%d cancelled=%d\n", $r['due'], $r['sent'], $r['retry'], $r['failed'], $r['cancelled']);
