<?php
$u = require_login();
if (empty($_SESSION['totp_setup_secret'])) {
    $_SESSION['totp_setup_secret'] = totp_new_secret();
}
$secret = $_SESSION['totp_setup_secret'];
$errors = [];
if (is_post()) {
    csrf_verify();
    if (totp_verify($secret, post('code'))) {
        update('users', ['totp_secret' => encrypt_field($secret), 'totp_enabled' => 1], 'id=?', [$u['id']]);
        unset($_SESSION['totp_setup_secret']);
        audit('user.2fa_enabled', 'user', $u['id']);
        flash('success', 'Two-factor authentication is now on.');
        redirect('index.php?page=account');
    }
    $errors[] = 'That code did not match. Check the time on your phone and try again.';
}
$uri = totp_uri($secret, $u['email'] ?: $u['phone'] ?: $u['name'], cfg('app_name'));
render_page('Set up two-factor authentication', 'twofa_setup', compact('secret', 'uri', 'errors'));
