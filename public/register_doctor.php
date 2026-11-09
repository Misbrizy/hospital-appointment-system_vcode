<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) { redirect('index.php'); }

$hospitals = rows("SELECT id, name, district FROM hospitals WHERE status='active' ORDER BY name");
$errors = [];
$done = false;

if (is_post()) {
    csrf_verify();
    $hospitalId = (int) post('hospital_id');
    $name = post('name'); $phone = normalize_phone(post('phone')); $email = post('email');
    $specialty = post('specialty');
    $pw = post('password'); $pw2 = post('password2');

    if (!rate_limit_allow('register_doctor:' . client_ip(), 6, 30)) { $errors[] = 'Wagerageje kenshi. Gerageza nyuma y\'akanya.'; }
    $hospital = $hospitalId ? row('SELECT * FROM hospitals WHERE id=? AND status=\'active\'', [$hospitalId]) : null;
    if (!$hospital) { $errors[] = 'Hitamo ibitaro ukoreramo.'; }
    if ($name === '') { $errors[] = 'Andika amazina yawe yombi.'; }
    if ($phone === null) { $errors[] = 'Andika telefoni nyayo.'; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Andika imeyili nyayo.'; }
    if ($pwErr = password_error($pw)) { $errors[] = $pwErr; }
    if ($pw !== $pw2) { $errors[] = 'Amagambo y\'ibanga ntabwo asa.'; }
    if (!$errors && row('SELECT id FROM users WHERE email=?', [$email])) { $errors[] = 'Iyi imeyili isanzwe ifite konti.'; }
    if (!$errors && row('SELECT id FROM users WHERE phone=?', [$phone])) { $errors[] = 'Iyi telefoni isanzwe ifite konti.'; }

    if (!$errors) {
        $uid = insert('users', [
            'hospital_id' => $hospitalId, 'role' => 'doctor', 'name' => $name, 'email' => $email,
            'phone' => $phone, 'specialty' => $specialty ?: null, 'password_hash' => hash_password($pw),
            'is_active' => 0, 'created_at' => now(),
        ]);
        $admins = rows("SELECT id FROM users WHERE hospital_id=? AND role='hospital_admin' AND is_active=1", [$hospitalId]);
        foreach ($admins as $adm) {
            notify((int) $adm['id'], 'New doctor awaiting approval: ' . $name, $specialty ?: '', url('index.php?page=users'), $hospitalId);
        }
        audit('doctor.self_register', 'user', $uid, null, ['hospital_id' => $hospitalId]);
        $done = true;
    }
}
?>
<!doctype html><html lang="<?= e(ui_lang()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e(t('docreg.title')) ?> · <?= e(cfg('app_name')) ?></title><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"></head>
<body class="auth-body"><main class="auth-card" style="max-width:460px">
  <?= lang_switcher() ?>
  <div class="brand"><span class="mark">H</span><span><?= e(cfg('app_name')) ?></span></div>
  <h1 style="font-size:1.25rem"><?= e(t('docreg.title')) ?></h1>
  <?php if ($done): ?>
    <div class="alert success"><?= e(t('docreg.pending')) ?></div>
    <p class="auth-foot"><a href="<?= e(url('login.php')) ?>"><?= e(t('common.sign_in')) ?></a></p>
  <?php else: ?>
  <p class="muted" style="margin-top:-8px"><?= e(t('docreg.subtitle')) ?></p>
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field"><label><?= e(t('docreg.hospital')) ?></label><select name="hospital_id" required>
      <option value=""><?= e(t('patreg.choose_hospital')) ?></option>
      <?php foreach ($hospitals as $h): ?><option value="<?= $h['id'] ?>" <?= selected(pv('hospital_id'), $h['id']) ?>><?= e($h['name']) ?><?= $h['district'] ? ' · ' . e($h['district']) : '' ?></option><?php endforeach; ?>
    </select></div>
    <div class="field"><label><?= e(t('docreg.full_name')) ?></label><input name="name" required value="<?= pv('name') ?>"></div>
    <div class="row2">
      <div class="field"><label><?= e(t('common.email')) ?></label><input name="email" type="email" required value="<?= pv('email') ?>"></div>
      <div class="field"><label><?= e(t('common.phone')) ?></label><input name="phone" type="tel" required value="<?= pv('phone') ?>"></div>
    </div>
    <div class="field"><label><?= e(t('docreg.specialty')) ?></label><input name="specialty" value="<?= pv('specialty') ?>"></div>
    <div class="row2">
      <div class="field"><label><?= e(t('common.password')) ?></label><input name="password" type="password" required></div>
      <div class="field"><label><?= e(t('common.confirm_password')) ?></label><input name="password2" type="password" required></div>
    </div>
    <button class="btn block" type="submit"><?= e(t('docreg.send_request')) ?></button>
  </form>
  <p class="auth-foot"><?= e(t('auth.already_have_account')) ?> <a href="<?= e(url('login.php')) ?>"><?= e(t('common.sign_in')) ?></a></p>
  <?php endif; ?>
</main></body></html>
