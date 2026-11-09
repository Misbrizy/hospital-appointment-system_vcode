<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$token = get('token');
$hash = hash('sha256', $token);
$pr = row('SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at > ?', [$hash, now()]);
$errors = [];
if (!$token || !$pr) {
    $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
} elseif (is_post()) {
    csrf_verify();
    $pw = post('password'); $pw2 = post('password2');
    if ($pwErr = password_error($pw)) { $errors[] = $pwErr; }
    elseif ($pw !== $pw2) { $errors[] = 'Passwords do not match.'; }
    else {
        transaction(function () use ($pr, $pw) {
            update('users', ['password_hash' => hash_password($pw), 'must_change_password' => 0, 'failed_attempts' => 0, 'locked_until' => null], 'id=?', [$pr['user_id']]);
            update('password_resets', ['used_at' => now()], 'id=?', [$pr['id']]);
        });
        flash('success', 'Password updated. Please sign in.');
        redirect('login.php');
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Choose a new password · <?= e(cfg('app_name')) ?></title><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"></head>
<body class="auth-body"><main class="auth-card">
  <div class="brand"><span class="mark">H</span><span><?= e(cfg('app_name')) ?></span></div>
  <h1 style="font-size:1.25rem">Choose a new password</h1>
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <?php if ($pr): ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="field"><label>New password</label><input name="password" type="password" required autofocus></div>
      <div class="field"><label>Confirm new password</label><input name="password2" type="password" required></div>
      <button class="btn block" type="submit">Set password</button>
    </form>
  <?php else: ?>
    <p><a class="btn" href="<?= e(url('forgot.php')) ?>">Request a new link</a></p>
  <?php endif; ?>
</main></body></html>
