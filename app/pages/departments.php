<?php
$u = require_role('hospital_admin');
$hid = current_hospital_id();
if (is_post()) {
    csrf_verify();
    $action = post('action');
    if ($action === 'add' && post('name') !== '') {
        insert('departments', ['hospital_id' => $hid, 'name' => post('name'), 'is_active' => 1]);
        flash('success', 'Department added.');
    } elseif ($action === 'toggle') {
        $d = row('SELECT * FROM departments WHERE id=? AND hospital_id=?', [(int) post('id'), $hid]);
        if ($d) { update('departments', ['is_active' => $d['is_active'] ? 0 : 1], 'id=?', [$d['id']]); }
    }
    redirect('index.php?page=departments');
}
$departments = rows('SELECT * FROM departments WHERE hospital_id=? ORDER BY name', [$hid]);
render_page('Departments', 'departments', compact('departments'));
