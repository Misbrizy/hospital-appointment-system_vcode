<?php
$u = require_role('hospital_admin');
$hid = current_hospital_id();

$departments = rows('SELECT id, name FROM departments WHERE hospital_id=? AND is_active=1 ORDER BY name', [$hid]);
if (is_post() && post('action') === 'approve') {
    csrf_verify();
    $id = (int) post('id');
    $target = row("SELECT * FROM users WHERE id=? AND hospital_id=? AND role<>'patient'", [$id, $hid]);
    if ($target && $target['role'] === 'doctor') {
        // A doctor must belong to a department, otherwise their appointments and notifications cannot show the right one.
        $dept = (int) post('department_id') ?: (int) $target['department_id'];
        if (!$dept || !department_in_hospital($dept, $hid)) {
            flash('error', 'Choose the department for Dr ' . $target['name'] . ' before approving.');
            redirect('index.php?page=users');
        }
        update('users', ['department_id' => $dept], 'id=?', [$id]);
    }
    if ($target) {
        update('users', ['is_active' => 1], 'id=?', [$id]);
        audit('user.approve', 'user', $id);
        notify($id, 'Konti yawe yemejwe', 'Ubu ushobora kwinjira muri sisitemu.', url('login.php'), $hid);
        flash('success', $target['name'] . ' yemejwe kandi ashobora kwinjira.');
    }
    redirect('index.php?page=users');
}

$pending = rows("SELECT * FROM users WHERE hospital_id=? AND role<>'patient' AND is_active=0 ORDER BY created_at", [$hid]);
$staff = rows('SELECT * FROM users WHERE hospital_id=? ORDER BY FIELD(role,"hospital_admin","doctor","nurse","receptionist","patient"), name', [$hid]);
render_page('Staff & roles', 'users', compact('staff', 'pending', 'departments'));
