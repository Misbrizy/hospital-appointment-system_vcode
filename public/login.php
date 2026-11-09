<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) { redirect('index.php'); }

if (!empty($_SESSION['2fa_pending'])) { redirect('twofa.php'); }

$error = '';
if (is_post()) {
    csrf_verify();
    [$ok, $msg] = attempt_login(post('identifier'), post('password'));
    if (!$ok) {
        $error = $msg;
    } elseif ($msg === '2fa') {
        redirect('twofa.php');
    } else {
        $to = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        redirect($to ?: 'index.php');
    }
}
$as = get('as'); // 'staff' | 'patient' | '' — purely presentational, chosen from the sign-in dialog
$heading = $as === 'patient' ? t('landing.patient_signin_title') : ($as === 'staff' ? t('landing.staff_signin_title') : t('auth.sign_in_title'));
?>
<!doctype html><html lang="<?= e(ui_lang()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e($heading) ?> · <?= e(cfg('app_name')) ?></title><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"></head>
<body class="auth-body"><main class="auth-card">
  <?= lang_switcher() ?>
  <div class="brand"><span class="mark">H</span><span><?= e(cfg('app_name')) ?></span></div>
  <?php if ($as): ?>
    <div class="ic" style="width:46px;height:46px;border-radius:12px;background:var(--teal-wash);color:var(--teal-deep);display:grid;place-items:center;margin-bottom:10px"><?= icon($as === 'patient' ? 'heart' : 'id') ?></div>
  <?php endif; ?>
  <h1 style="font-size:1.25rem"><?= e($heading) ?></h1>
  <p class="muted" style="margin-top:-8px"><?= $as === 'patient' ? e(t('landing.patient_login_sub')) : ($as === 'staff' ? e(t('landing.staff_login_sub')) : e(cfg('app_name')) . '.') ?></p>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <?php foreach (flashes() as $f): ?><div class="alert <?= e($f['t'] === 'error' ? 'error' : 'info') ?>"><?= e($f['m']) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field"><label for="identifier"><?= e(t('auth.identifier')) ?></label><input id="identifier" name="identifier" type="text" required autofocus value="<?= pv('identifier') ?>"></div>
    <div class="field"><label for="password"><?= e(t('common.password')) ?></label><input id="password" name="password" type="password" required></div>
    <button class="btn block" type="submit"><?= e(t('common.sign_in')) ?></button>
  </form>
  <p class="auth-foot"><a href="<?= e(url('forgot.php')) ?>"><?= e(t('auth.forgot')) ?></a></p>
  <p class="auth-foot muted"><?= e(t('auth.are_you_patient')) ?> <a href="<?= e(url('register_patient.php')) ?>"><?= e(t('auth.register_here')) ?></a> · <?= e(t('auth.are_you_doctor')) ?> <a href="<?= e(url('register_doctor.php')) ?>"><?= e(t('auth.register_here')) ?></a></p>
  <p class="auth-foot muted"><?= e(t('auth.new_hospital')) ?> <a href="<?= e(url('register.php')) ?>"><?= e(t('auth.start_trial')) ?></a></p>
</main></body></html>
