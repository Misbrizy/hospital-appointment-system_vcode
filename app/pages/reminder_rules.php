<?php
$u = require_role('hospital_admin');
$hid = current_hospital_id();
$errors = [];

if (is_post()) {
    csrf_verify();
    $action = post('action');
    if ($action === 'add') {
        $label = post('label'); $days = (int) post('days_before'); $time = post('send_time');
        if ($label === '') { $errors[] = 'Label is required.'; }
        if (!valid_time($time)) { $errors[] = 'Enter a valid send time.'; }
        if (!$errors) {
            insert('reminder_rules', ['hospital_id' => $hid, 'label' => $label, 'days_before' => max(0, $days), 'send_time' => $time . ':00', 'template_code' => post('template_code'), 'is_active' => 1]);
            flash('success', 'Reminder rule added.');
            redirect('index.php?page=reminder_rules');
        }
    } elseif ($action === 'toggle') {
        $id = (int) post('id');
        $r = row('SELECT * FROM reminder_rules WHERE id=? AND hospital_id=?', [$id, $hid]);
        if ($r) { update('reminder_rules', ['is_active' => $r['is_active'] ? 0 : 1], 'id=?', [$id]); }
        redirect('index.php?page=reminder_rules');
    } elseif ($action === 'delete') {
        q('DELETE FROM reminder_rules WHERE id=? AND hospital_id=?', [(int) post('id'), $hid]);
        flash('success', 'Reminder rule removed.');
        redirect('index.php?page=reminder_rules');
    }
}
$rules = rows('SELECT * FROM reminder_rules WHERE hospital_id=? ORDER BY days_before DESC', [$hid]);
$templateCodes = rows("SELECT DISTINCT code FROM sms_templates WHERE hospital_id=? AND code LIKE 'reminder%'", [$hid]);
render_page('Reminder rules', 'reminder_rules', compact('rules', 'errors', 'templateCodes'));
