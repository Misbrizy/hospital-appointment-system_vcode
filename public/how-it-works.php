<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$navActive = 'how';
?>
<!doctype html><html lang="<?= e(ui_lang()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e(t('landing.how_title')) ?> · <?= e(cfg('app_name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?= view('_marketing_css') ?>
</head>
<body>
<div class="page-hero">
  <?= view('_marketing_nav', ['navActive' => $navActive]) ?>
  <h1 style="margin-top:28px"><?= e(t('landing.how_title')) ?></h1>
  <p><?= e(t('landing.how_lead')) ?></p>
</div>

<section>
  <div class="steps">
    <div class="step"><h3>Request</h3><p class="muted">A patient requests an appointment themselves on the portal, or reception/a doctor books one for them. The system assigns an available doctor automatically.</p></div>
    <div class="step"><h3>Confirm</h3><p class="muted">A confirmation is sent right away, on whichever channel — SMS, email or WhatsApp — the patient opted into.</p></div>
    <div class="step"><h3>Remind</h3><p class="muted">Reminders queue automatically for 3 days before, 1 day before, and the morning of the visit — nobody has to remember to send anything.</p></div>
    <div class="step"><h3>Arrive &amp; consult</h3><p class="muted">Reception checks the patient in, the doctor sees them on a live queue board, and records the visit — diagnosis, prescription, follow-up — when done.</p></div>
  </div>
</section>

<section style="background:var(--teal-wash)">
  <div class="section-title"><h2>If plans change</h2></div>
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
    <div class="benefit"><div class="ic"><?= icon('refresh') ?></div><div><h3 style="font-size:1rem">Patient requests a new time</h3><p class="muted">From their own account — the hospital approves or declines, and they're notified either way.</p></div></div>
    <div class="benefit"><div class="ic"><?= icon('cal') ?></div><div><h3 style="font-size:1rem">Doctor moves the date</h3><p class="muted">Directly from their appointment list — the patient is notified automatically.</p></div></div>
    <div class="benefit"><div class="ic"><?= icon('clock') ?></div><div><h3 style="font-size:1rem">Slot already taken?</h3><p class="muted">The booking form shows live availability, so a double-booking is caught before it happens.</p></div></div>
    <div class="benefit"><div class="ic"><?= icon('shield') ?></div><div><h3 style="font-size:1rem">Nothing lost</h3><p class="muted">Every change is logged in the hospital's audit trail.</p></div></div>
  </div>
</section>

<?= view('_marketing_foot') ?>
</body></html>
