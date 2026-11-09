<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (empty($_SESSION['2fa_pending'])) { redirect('login.php'); }
$u = row('SELECT * FROM users WHERE id=?', [$_SESSION['2fa_pending']]);
if (!$u) { $_SESSION = []; redirect('login.php'); }

$error = '';
if (is_post()) {
    csrf_verify();
    if (!rate_limit_allow('2fa:' . $u['id'], 10, 15)) {
        $error = 'Too many attempts. Please wait a few minutes.';
    } elseif (totp_verify(decrypt_field($u['totp_secret']) ?? '', post('code'))) {
        finish_login($u);
        redirect('index.php');
    } else {
        $error = 'That code is incorrect or expired.';
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Verify · <?= e(cfg('app_name')) ?></title><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"></head>
<body class="auth-body"><main class="auth-card">
  <div class="brand"><span class="mark">H</span><span><?= e(cfg('app_name')) ?></span></div>
  <h1 style="font-size:1.25rem">Enter your 6-digit code</h1>
  <p class="muted" style="margin-top:-8px">From your authenticator app.</p>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field"><input name="code" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus placeholder="000000" style="text-align:center;font-size:1.4rem;letter-spacing:.3em"></div>
    <button class="btn block" type="submit">Verify</button>
  </form>
  <p class="auth-foot"><a href="<?= e(url('logout.php')) ?>">Cancel and sign in as someone else</a></p>
</main></body></html>
