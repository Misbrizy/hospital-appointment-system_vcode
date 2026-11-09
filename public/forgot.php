<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$done = false;
if (is_post()) {
    csrf_verify();
    $email = post('email');
    if (rate_limit_allow('forgot:' . client_ip(), 6, 30) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $u = row('SELECT id, name FROM users WHERE email=? AND is_active=1', [$email]);
        if ($u) {
            $token = bin2hex(random_bytes(32));
            insert('password_resets', ['user_id' => $u['id'], 'token_hash' => hash('sha256', $token), 'expires_at' => date('Y-m-d H:i:s', time() + 3600), 'created_at' => now()]);
            $link = abs_url('reset.php?token=' . $token);
            $sent = @mail($email, 'Reset your password — ' . cfg('app_name'), "Hello {$u['name']},\n\nUse this link within one hour to set a new password:\n$link\n\nIf you did not request this, ignore this email.");
            if (!$sent) {
                error_log("password reset link for user {$u['id']}: $link");
            }
        }
    }
    $done = true; // Always show the same message, whether or not the email matched an account.
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Reset password · <?= e(cfg('app_name')) ?></title><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"></head>
<body class="auth-body"><main class="auth-card">
  <div class="brand"><span class="mark">H</span><span><?= e(cfg('app_name')) ?></span></div>
  <h1 style="font-size:1.25rem">Reset your password</h1>
  <?php if ($done): ?>
    <div class="alert info">If that email matches an account, a reset link has been sent. It expires in one hour. If you don't have access to that email, ask your hospital administrator to reset it for you.</div>
  <?php else: ?>
    <p class="muted" style="margin-top:-8px">We'll email you a link to choose a new password.</p>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label>Email</label><input name="email" type="email" required autofocus></div>
      <button class="btn block" type="submit">Send reset link</button>
    </form>
  <?php endif; ?>
  <p class="auth-foot"><a href="<?= e(url('login.php')) ?>">Back to sign in</a></p>
</main></body></html>
