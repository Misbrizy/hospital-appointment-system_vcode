<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) { redirect('index.php'); }

$hospitals = rows("SELECT id, name, district FROM hospitals WHERE status='active' ORDER BY name");
$errors = [];

if (is_post()) {
    csrf_verify();
    $hospitalId = (int) post('hospital_id');
    $first = post('first_name'); $last = post('last_name');
    $phone = normalize_phone(post('phone'));
    $email = post('email');
    $whatsapp = post('whatsapp') !== '' ? normalize_phone(post('whatsapp')) : null;
    $pw = post('password'); $pw2 = post('password2');

    if (!rate_limit_allow('register_patient:' . client_ip(), 8, 30)) { $errors[] = 'Wagerageje kenshi. Gerageza nyuma y\'akanya.'; }
    $hospital = $hospitalId ? row('SELECT * FROM hospitals WHERE id=? AND status=\'active\'', [$hospitalId]) : null;
    if (!$hospital) { $errors[] = 'Hitamo ibitaro byawe.'; }
    if ($first === '') { $errors[] = 'Andika izina ryawe rya mbere.'; }
    if ($last === '') { $errors[] = 'Andika izina ryawe rya nyuma.'; }
    if ($phone === null) { $errors[] = 'Andika telefoni nyayo (urugero: 07XXXXXXXX).'; }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Imeyili si nziza.'; }
    if (post('whatsapp') !== '' && $whatsapp === null) { $errors[] = 'Numero ya WhatsApp si nziza.'; }
    if ($pwErr = password_error($pw)) { $errors[] = $pwErr; }
    if ($pw !== $pw2) { $errors[] = 'Amagambo y\'ibanga ntabwo asa.'; }
    if (!$errors && $phone && row('SELECT id FROM users WHERE phone=?', [$phone])) { $errors[] = 'Iyi telefoni isanzwe ifite konti.'; }
    if (!$errors && $email && row('SELECT id FROM users WHERE email=?', [$email])) { $errors[] = 'Iyi imeyili isanzwe ifite konti.'; }

    if (!$errors) {
        $smsLang = ui_lang() === 'fr' ? 'en' : ui_lang(); // SMS templates ship in rw/en only
        $uid = transaction(function () use ($hospitalId, $first, $last, $phone, $email, $whatsapp, $pw, $smsLang) {
            $userId = insert('users', [
                'hospital_id' => $hospitalId, 'role' => 'patient', 'name' => trim("$first $last"),
                'email' => $email ?: null, 'phone' => $phone, 'password_hash' => hash_password($pw),
                'is_active' => 1, 'created_at' => now(),
            ]);
            $seq = next_seq($hospitalId, 'patient_seq');
            $patientNo = 'PT-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            insert('patients', [
                'hospital_id' => $hospitalId, 'patient_no' => $patientNo, 'user_id' => $userId,
                'first_name' => $first, 'last_name' => $last, 'gender' => 'female', 'phone' => $phone,
                'email' => $email ?: null, 'whatsapp_phone' => $whatsapp,
                'preferred_language' => $smsLang, 'sms_opt_in' => 1,
                'email_opt_in' => $email ? 1 : 0, 'whatsapp_opt_in' => $whatsapp ? 1 : 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return $userId;
        });
        $newUser = row('SELECT * FROM users WHERE id=?', [$uid]);
        finish_login($newUser);
        flash('success', 'Murakaza neza! Ubu ushobora gusaba gahunda yo kwisuzumisha.');
        redirect('index.php?page=portal');
    }
}
?>
<!doctype html><html lang="<?= e(ui_lang()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e(t('patreg.title')) ?> · <?= e(cfg('app_name')) ?></title><link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"></head>
<body class="auth-body"><main class="auth-card" style="max-width:440px">
  <?= lang_switcher() ?>
  <div class="brand"><span class="mark">H</span><span><?= e(cfg('app_name')) ?></span></div>
  <h1 style="font-size:1.25rem"><?= e(t('patreg.title')) ?></h1>
  <p class="muted" style="margin-top:-8px"><?= e(t('patreg.subtitle')) ?></p>
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field"><label><?= e(t('patreg.hospital')) ?></label><select name="hospital_id" required>
      <option value=""><?= e(t('patreg.choose_hospital')) ?></option>
      <?php foreach ($hospitals as $h): ?><option value="<?= $h['id'] ?>" <?= selected(pv('hospital_id'), $h['id']) ?>><?= e($h['name']) ?><?= $h['district'] ? ' · ' . e($h['district']) : '' ?></option><?php endforeach; ?>
    </select></div>
    <div class="row2">
      <div class="field"><label><?= e(t('patreg.first_name')) ?></label><input name="first_name" required value="<?= pv('first_name') ?>"></div>
      <div class="field"><label><?= e(t('patreg.last_name')) ?></label><input name="last_name" required value="<?= pv('last_name') ?>"></div>
    </div>
    <div class="field"><label><?= e(t('common.phone')) ?></label><input name="phone" type="tel" required value="<?= pv('phone') ?>"></div>
    <div class="field"><label><?= e(t('patreg.email_optional')) ?></label><input name="email" type="email" value="<?= pv('email') ?>"></div>
    <div class="field"><label>WhatsApp number (if it is different from the previous number)</label><input name="whatsapp" type="tel" value="<?= pv('whatsapp') ?>"></div>
    <div class="row2">
      <div class="field"><label><?= e(t('common.password')) ?></label><input name="password" type="password" required></div>
      <div class="field"><label><?= e(t('common.confirm_password')) ?></label><input name="password2" type="password" required></div>
    </div>
    <button class="btn block" type="submit"><?= e(t('patreg.create_account')) ?></button>
  </form>
  <p class="auth-foot"><?= e(t('auth.already_have_account')) ?> <a href="<?= e(url('login.php')) ?>"><?= e(t('common.sign_in')) ?></a></p>
</main></body></html>
