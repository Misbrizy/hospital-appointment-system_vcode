<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) { redirect('index.php'); }

$errors = [];
if (is_post()) {
    csrf_verify();
    $hospitalName = post('hospital_name');
    $district = post('district');
    $adminName = post('admin_name');
    $email = post('email');
    $phone = normalize_phone(post('phone'));
    $pw = post('password');
    $pw2 = post('password2');

    if (!rate_limit_allow('register:' . client_ip(), 5, 60)) { $errors[] = 'Too many attempts. Please try again later.'; }
    if ($hospitalName === '') { $errors[] = 'Hospital name is required.'; }
    if ($adminName === '') { $errors[] = 'Your name is required.'; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
    if ($phone === null) { $errors[] = 'A valid phone number is required (e.g. 07XXXXXXXX).'; }
    if ($pwErr = password_error($pw)) { $errors[] = $pwErr; }
    if ($pw !== $pw2) { $errors[] = 'Passwords do not match.'; }
    if (!$errors && row('SELECT id FROM users WHERE email=? OR phone=?', [$email, $phone])) { $errors[] = 'An account with that email or phone already exists.'; }

    if (!$errors) {
        $planId = (int) val("SELECT id FROM plans WHERE code='basic'");
        transaction(function () use ($hospitalName, $district, $adminName, $email, $phone, $pw, $planId) {
            $hid = insert('hospitals', [
                'name' => $hospitalName, 'district' => $district ?: null, 'plan_id' => $planId,
                'status' => 'active', 'is_trial' => 1,
                'subscription_ends_at' => date('Y-m-d', strtotime('+' . (int) cfg('trial_days', 14) . ' days')),
                'created_at' => now(),
            ]);
            insert('users', [
                'hospital_id' => $hid, 'role' => 'hospital_admin', 'name' => $adminName, 'email' => $email,
                'phone' => $phone, 'password_hash' => hash_password($pw), 'is_active' => 1, 'created_at' => now(),
            ]);
            seed_default_templates($hid);
            seed_default_reminder_rules($hid);
            insert('departments', ['hospital_id' => $hid, 'name' => 'General']);
        });
        flash('success', 'Your hospital account is ready. Sign in to get started — your free trial runs for ' . (int) cfg('trial_days', 14) . ' days.');
        redirect('login.php');
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Start your trial · <?= e(cfg('app_name')) ?></title><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"></head>
<body class="auth-body"><main class="auth-card" style="max-width:460px">
  <div class="brand"><span class="mark">H</span><span><?= e(cfg('app_name')) ?></span></div>
  <h1 style="font-size:1.25rem">Start your free trial</h1>
  <p class="muted" style="margin-top:-8px"><?= (int) cfg('trial_days', 14) ?> days, no card required. You'll be the hospital administrator.</p>
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field"><label>Hospital / clinic name</label><input name="hospital_name" required value="<?= pv('hospital_name') ?>"></div>
    <div class="field"><label>District</label><input name="district" value="<?= pv('district') ?>"></div>
    <div class="field"><label>Your full name</label><input name="admin_name" required value="<?= pv('admin_name') ?>"></div>
    <div class="row2">
      <div class="field"><label>Work email</label><input name="email" type="email" required value="<?= pv('email') ?>"></div>
      <div class="field"><label>Phone</label><input name="phone" type="tel" required value="<?= pv('phone') ?>"></div>
    </div>
    <div class="row2">
      <div class="field"><label>Password</label><input name="password" type="password" required></div>
      <div class="field"><label>Confirm password</label><input name="password2" type="password" required></div>
    </div>
    <button class="btn block" type="submit">Create hospital account</button>
  </form>
  <p class="auth-foot">Already have an account? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
</main></body></html>
