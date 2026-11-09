<?php
$u = require_role('hospital_admin');
$hid = current_hospital_id();
$id = (int) get('id');
$staffUser = scoped_or_404('users', $id);
$errors = [];
$departments = rows('SELECT * FROM departments WHERE hospital_id=? ORDER BY name', [$hid]);

if (is_post()) {
    csrf_verify();
    if (post('toggle_active')) {
        if ($id === $u['id']) { flash('error', 'You cannot disable your own account.'); redirect('index.php?page=user_edit&id=' . $id); }
        update('users', ['is_active' => $staffUser['is_active'] ? 0 : 1], 'id=?', [$id]);
        audit('user.toggle_active', 'user', $id);
        flash('success', 'Account updated.');
        redirect('index.php?page=user_edit&id=' . $id);
    }
    if (post('reset_password')) {
        $temp = random_password();
        update('users', ['password_hash' => hash_password($temp), 'must_change_password' => 1, 'failed_attempts' => 0, 'locked_until' => null], 'id=?', [$id]);
        audit('user.reset_password', 'user', $id);
        flash('success', "New temporary password: $temp — share it securely.");
        redirect('index.php?page=user_edit&id=' . $id);
    }
    $name = post('name'); $email = post('email'); $phone = normalize_phone(post('phone'));
    if ($name === '') { $errors[] = 'Name is required.'; }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Enter a valid email.'; }
    if (!$errors && $email && row('SELECT id FROM users WHERE email=? AND id<>?', [$email, $id])) { $errors[] = 'That email is already in use.'; }
    if (!$errors) {
        update('users', ['name' => $name, 'email' => $email ?: null, 'phone' => $phone, 'department_id' => (int) post('department_id') ?: null, 'specialty' => post('specialty') ?: null], 'id=?', [$id]);
        // If a doctor moves department, their UPCOMING open appointments follow them so nothing shows the old department.
        $newDept = (int) post('department_id') ?: null;
        if ($staffUser['role'] === 'doctor' && $newDept && (int) $staffUser['department_id'] !== $newDept) {
            q("UPDATE appointments SET department_id=? WHERE doctor_id=? AND status IN ('scheduled','confirmed') AND appt_date >= CURDATE()", [$newDept, $id]);
        }
        audit('user.update', 'user', $id);
        flash('success', 'Updated.');
        redirect('index.php?page=users');
    }
}
render_page('Edit staff member', 'user_form', compact('errors', 'departments', 'staffUser'));
