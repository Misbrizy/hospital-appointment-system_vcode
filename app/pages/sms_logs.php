<?php
$u = require_login();
require_can('sms.view');
$hid = current_hospital_id();

if (is_post() && can('sms.manage')) {
    csrf_verify();
    $action = post('action');
    if ($action === 'run_now') {
        $r = reminders_run_due('manual');
        flash($r['skipped'] ? 'info' : 'success', $r['skipped'] ? 'The scheduler is already running — try again in a moment.'
            : sprintf('Scheduler ran: %d due, %d sent, %d waiting to retry, %d failed, %d cancelled.', $r['due'], $r['sent'], $r['retry'], $r['failed'], $r['cancelled']));
    } elseif ($action === 'retry') {
        $log = row("SELECT id, status FROM sms_logs WHERE id=? AND hospital_id=?", [(int) post('id'), $hid]);
        if ($log && $log['status'] === 'failed') {
            update('sms_logs', ['status' => 'pending', 'attempts' => 0, 'next_attempt_at' => null, 'scheduled_at' => now(), 'error' => null], 'id=?', [$log['id']]);
            audit('sms.retry', 'sms_log', (int) $log['id']);
            flash('success', 'Queued for another delivery attempt.');
        }
    }
    redirect('index.php?page=sms_logs');
}

$status = get('status');
$channel = get('channel');
$where = 'hospital_id=?';
$params = [$hid];
if ($status !== '') { $where .= ' AND status=?'; $params[] = $status; }
if (in_array($channel, ['sms', 'email', 'whatsapp'], true)) { $where .= ' AND channel=?'; $params[] = $channel; }
$total = (int) val("SELECT COUNT(*) FROM sms_logs WHERE $where", $params);
$p = paginate($total, 30);
$logs = rows("SELECT * FROM sms_logs WHERE $where ORDER BY created_at DESC, id DESC LIMIT {$p['per']} OFFSET {$p['offset']}", $params);
$attempts = [];
if ($logs) {
    $in = implode(',', array_fill(0, count($logs), '?'));
    foreach (rows("SELECT * FROM sms_attempts WHERE sms_log_id IN ($in) ORDER BY attempt_no", array_column($logs, 'id')) as $a) { $attempts[$a['sms_log_id']][] = $a; }
}
$summary = row("SELECT COUNT(*) total, SUM(status='delivered') delivered, SUM(status='sent') sent, SUM(status='failed') failed, SUM(status='pending') pending FROM sms_logs WHERE hospital_id=?", [$hid]);
$scheduler = scheduler_status();
// Messages that should already have gone out (more than 5 minutes ago) but are still waiting, with no retry scheduled.
$overdue = (int) val("SELECT COUNT(*) FROM sms_logs WHERE hospital_id=? AND status='pending' AND scheduled_at < ? AND (next_attempt_at IS NULL OR next_attempt_at < ?)", [$hid, date('Y-m-d H:i:s', time() - 300), now()]);
render_page('Message delivery', 'sms_logs', compact('logs', 'p', 'status', 'channel', 'summary', 'attempts', 'scheduler', 'overdue'));
