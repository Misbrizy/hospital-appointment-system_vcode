<?php
$u = require_login();
$errors = [];
if (is_post()) {
    csrf_verify();
    $action = post('form');
    if ($action === 'profile') {
        update('users', ['name' => post('name'), 'email' => post('email') ?: null], 'id=?', [$u['id']]);
        flash('success', 'Profile updated.');
        redirect('index.php?page=account');
    } elseif ($action === 'password') {
        $cur = row('SELECT password_hash FROM users WHERE id=?', [$u['id']]);
        if (!password_verify(post('current_password'), $cur['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif ($err = password_error(post('new_password'))) {
            $errors[] = $err;
        } elseif (post('new_password') !== post('new_password2')) {
            $errors[] = 'New passwords do not match.';
        } else {
            update('users', ['password_hash' => hash_password(post('new_password')), 'must_change_password' => 0], 'id=?', [$u['id']]);
            audit('user.password_change', 'user', $u['id']);
            flash('success', 'Password changed.');
            redirect('index.php?page=account');
        }
    } elseif ($action === 'disable_2fa') {
        update('users', ['totp_enabled' => 0, 'totp_secret' => null], 'id=?', [$u['id']]);
        flash('success', 'Two-factor authentication turned off.');
        redirect('index.php?page=account');
    }
}
$fresh = row('SELECT * FROM users WHERE id=?', [$u['id']]);
render_page('My account', 'account', compact('u', 'errors', 'fresh'));
