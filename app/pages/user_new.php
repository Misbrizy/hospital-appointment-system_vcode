<?php
$u = require_role('hospital_admin');
$hid = current_hospital_id();
$errors = [];
$departments = rows('SELECT * FROM departments WHERE hospital_id=? ORDER BY name', [$hid]);
if (is_post()) {
    csrf_verify();
    $name = post('name'); $email = post('email'); $phone = normalize_phone(post('phone')); $role = post('role');
    if ($name === '') { $errors[] = 'Name is required.'; }
    if (!in_array($role, ['hospital_admin','doctor','receptionist','nurse','finance'], true)) { $errors[] = 'Choose a valid role.'; }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Enter a valid email.'; }
    if ($email === '' && $phone === null) { $errors[] = 'Provide an email or a valid phone number.'; }
    if (!$errors && $email && row('SELECT id FROM users WHERE email=?', [$email])) { $errors[] = 'That email is already in use.'; }
    if (!$errors && $phone && row('SELECT id FROM users WHERE phone=?', [$phone])) { $errors[] = 'That phone number is already in use.'; }

    if (!$errors) {
        $temp = random_password();
        $id = insert('users', [
            'hospital_id' => $hid, 'role' => $role, 'department_id' => (int) post('department_id') ?: null,
            'name' => $name, 'email' => $email ?: null, 'phone' => $phone, 'specialty' => post('specialty') ?: null,
            'password_hash' => hash_password($temp), 'must_change_password' => 1, 'is_active' => 1, 'created_at' => now(),
        ]);
        audit('user.create', 'user', $id, null, ['role' => $role]);
        flash('success', "Staff account created. Temporary password: $temp — share it securely; they'll be asked to change it on first sign-in.");
        redirect('index.php?page=users');
    }
}
render_page('Add staff member', 'user_form', compact('errors', 'departments') + ['staffUser' => null]);
