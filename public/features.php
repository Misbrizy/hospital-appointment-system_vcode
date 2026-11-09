<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (current_user()) { /* staff/patients can still view marketing pages while logged out of them; no redirect needed */ }
$navActive = 'features';
?>
<!doctype html><html lang="<?= e(ui_lang()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e(t('landing.features_title')) ?> · <?= e(cfg('app_name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?= view('_marketing_css') ?>
</head>
<body>
<div class="page-hero">
  <?= view('_marketing_nav', ['navActive' => $navActive]) ?>
  <h1 style="margin-top:28px"><?= e(t('landing.features_title')) ?></h1>
  <p><?= e(t('landing.features_lead')) ?></p>
</div>

<section>
  <div class="feat-grid">
    <div class="feat"><div class="ic"><?= icon('cal') ?></div><h3>Appointment scheduling</h3><p class="muted">Book, reschedule or cancel across departments and doctors, with a live queue board at reception.</p></div>
    <div class="feat"><div class="ic"><?= icon('send') ?></div><h3>Automatic SMS, email &amp; WhatsApp reminders</h3><p class="muted">3 days before, 1 day before, and on the day — on whichever channels each patient prefers, in their chosen language.</p></div>
    <div class="feat"><div class="ic"><?= icon('heart') ?></div><h3>Antenatal care tracking</h3><p class="muted">LMP, expected delivery date and a full ANC visit schedule, with reminders for every visit.</p></div>
    <div class="feat"><div class="ic"><?= icon('shield') ?></div><h3>Role-based access</h3><p class="muted">Doctors, nurses, reception, finance and admins each see only what their role needs — with a full audit trail.</p></div>
    <div class="feat"><div class="ic"><?= icon('chart') ?></div><h3>Reports &amp; analytics</h3><p class="muted">Hospital-wide or a single patient's history — appointments, no-shows and reminder delivery at a glance.</p></div>
    <div class="feat"><div class="ic"><?= icon('building') ?></div><h3>Built for many hospitals</h3><p class="muted">Each hospital's patients and records stay completely separate on one shared, well-maintained system.</p></div>
    <div class="feat"><div class="ic"><?= icon('users') ?></div><h3>Patient self-service</h3><p class="muted">Patients register and request appointments themselves — the system assigns an available doctor automatically.</p></div>
    <div class="feat"><div class="ic"><?= icon('id') ?></div><h3>Doctor self-registration</h3><p class="muted">Doctors can request an account directly; a hospital administrator approves before it goes live.</p></div>
    <div class="feat"><div class="ic"><?= icon('card') ?></div><h3>Finance</h3><p class="muted">Invoices, payments and expenses for the hospital, kept separate from clinical records.</p></div>
  </div>
</section>

<section style="background:var(--teal-wash);text-align:center">
  <h2>Ready to see it in your hospital?</h2>
  <p class="muted">Start a free trial, no card required.</p>
  <a class="btn" href="<?= e(url('register.php')) ?>"><?= e(t('landing.get_started')) ?></a>
</section>

<?= view('_marketing_foot') ?>
</body></html>
