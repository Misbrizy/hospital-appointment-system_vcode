<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$navActive = 'pricing';
$plans = rows('SELECT * FROM plans WHERE is_active=1 ORDER BY sort_order');
?>
<!doctype html><html lang="<?= e(ui_lang()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e(t('landing.pricing_title')) ?> · <?= e(cfg('app_name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?= view('_marketing_css') ?>
</head>
<body>
<div class="page-hero">
  <?= view('_marketing_nav', ['navActive' => $navActive]) ?>
  <h1 style="margin-top:28px"><?= e(t('landing.pricing_title')) ?></h1>
  <p><?= e(t('landing.pricing_lead')) ?></p>
</div>

<section>
  <div class="pricing">
    <?php foreach ($plans as $p): ?>
    <div class="plan <?= $p['code'] === 'professional' ? 'pop' : '' ?>">
      <?php if ($p['code'] === 'professional'): ?><span class="tag">Most popular</span><?php endif; ?>
      <h3><?= e($p['name']) ?></h3>
      <div class="price"><?= $p['price_month'] > 0 ? money($p['price_month']) . '<span style="font-size:.9rem;color:var(--ink-soft)">/mo</span>' : 'Contact us' ?></div>
      <ul>
        <li><?= $p['max_patients'] ? number_format((int) $p['max_patients']) . ' patients' : 'Unlimited patients' ?></li>
        <li><?= $p['max_sms_month'] ? number_format((int) $p['max_sms_month']) . ' reminders / month' : 'Unlimited reminders' ?></li>
        <li><?= (int) $p['max_branches'] > 1 ? (int) $p['max_branches'] . ' branches' : 'Single branch' ?></li>
        <li><?= $p['advanced_reports'] ? 'Advanced reports' : 'Standard reports' ?></li>
      </ul>
      <a class="btn <?= $p['code'] === 'professional' ? '' : 'ghost' ?> block" href="<?= e(url('register.php')) ?>">Start free trial</a>
    </div>
    <?php endforeach; ?>
  </div>
  <p style="text-align:center;margin-top:30px" class="muted">Already a customer and want to change plans? Sign in and open <strong>Billing &amp; plan</strong>.</p>
</section>

<?= view('_marketing_foot') ?>
</body></html>
