<?php $navActive = 'home'; ?>
<!doctype html><html lang="<?= e(ui_lang()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e(cfg('app_name')) ?></title>
<meta name="description" content="Helps hospitals and clinics manage appointments and automatically remind patients by SMS, email or WhatsApp.">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?= view('_marketing_css') ?>
</head>
<body>
<div class="hero">
  <?= view('_marketing_nav', ['navActive' => $navActive]) ?>
  <div class="hero-inner" style="margin-top:56px">
    <div>
      <h1><?= e(t('landing.hero_title')) ?></h1>
      <p class="lead"><?= e(t('landing.hero_lead')) ?></p>
      <div class="cta">
        <a class="btn white" href="<?= e(url('register.php')) ?>"><?= e(t('landing.get_started')) ?></a>
        <a class="btn outline" href="<?= e(url('index.php#demo')) ?>"><?= e(t('landing.request_demo')) ?></a>
      </div>
      <p style="margin-top:14px;color:#CFE6E2;font-size:.85rem"><?= e(t('auth.are_you_patient')) ?> <a href="<?= e(url('register_patient.php')) ?>" style="color:#fff;text-decoration:underline"><?= e(t('auth.register_here')) ?></a> · <?= e(t('auth.are_you_doctor')) ?> <a href="<?= e(url('register_doctor.php')) ?>" style="color:#fff;text-decoration:underline"><?= e(t('auth.register_here')) ?></a></p>
      <p style="margin-top:18px;color:#9FC7C1;font-size:.86rem"><?= e(t('landing.trial_note', ['days' => (int) cfg('trial_days', 14)])) ?></p>
    </div>
    <div class="phone-mock">
      <div class="screen">
        <div class="app-head">XYZ Hospital</div>
        <div class="sms-bubble">Muraho Alice, turakwibutsa ko ufite gahunda yo kwisuzumisha ku wa 15/09/2026 saa 09:00 kuri XYZ Hospital.</div>
        <div style="height:10px"></div>
        <div class="sms-bubble" style="background:var(--leaf-wash);color:#1E5B41">Hello Eric, today you have a follow-up at 09:30 at XYZ Hospital. See you soon.</div>
        <div style="margin-top:18px;font-size:.72rem;color:var(--ink-soft)">Delivered · 2 of 2</div>
      </div>
    </div>
  </div>
</div>

<section>
  <div class="section-title"><h2><?= e(t('landing.features_title')) ?></h2><p class="muted"><?= e(t('landing.features_lead')) ?></p></div>
  <div class="feat-grid">
    <div class="feat"><div class="ic"><?= icon('cal') ?></div><h3><?= e(t('landing.features_title')) ?></h3><p class="muted"><?= e(t('landing.hero_lead')) ?></p></div>
    <div class="feat"><div class="ic"><?= icon('send') ?></div><h3><?= e(t('landing.how_title')) ?></h3><p class="muted"><?= e(t('landing.how_lead')) ?></p></div>
    <div class="feat"><div class="ic"><?= icon('card') ?></div><h3><?= e(t('landing.pricing_title')) ?></h3><p class="muted"><?= e(t('landing.pricing_lead')) ?></p></div>
  </div>
  <p style="text-align:center;margin-top:30px">
    <a class="btn" href="<?= e(url('features.php')) ?>"><?= e(t('landing.features_title')) ?> →</a>
    <a class="btn ghost" href="<?= e(url('how-it-works.php')) ?>"><?= e(t('landing.how_title')) ?> →</a>
    <a class="btn ghost" href="<?= e(url('pricing.php')) ?>"><?= e(t('landing.pricing_title')) ?> →</a>
  </p>
</section>

<section id="demo" style="background:var(--teal-wash)">
  <div class="section-title"><h2><?= e(t('landing.demo_title')) ?></h2><p class="muted">Prefer a walkthrough first? Leave your details and we'll reach out.</p></div>
  <div class="card" style="max-width:480px;margin:0 auto">
    <?php if (!empty($demo_sent)): ?>
      <div class="alert success">Thank you — we'll be in touch shortly.</div>
    <?php else: ?>
    <form method="post" action="<?= e(url('demo.php')) ?>">
      <?= csrf_field() ?>
      <div class="field"><label>Your name</label><input name="name" required></div>
      <div class="field"><label>Hospital / clinic</label><input name="hospital"></div>
      <div class="row2">
        <div class="field"><label>Phone</label><input name="phone" type="tel"></div>
        <div class="field"><label>Email</label><input name="email" type="email"></div>
      </div>
      <div class="field"><label>Message</label><textarea name="message" rows="3"></textarea></div>
      <button class="btn block" type="submit">Request demo</button>
    </form>
    <?php endif; ?>
  </div>
</section>

<?= view('_marketing_foot') ?>
</body></html>
