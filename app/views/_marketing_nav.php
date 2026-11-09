<?php $cur = $navActive ?? ''; ?>
<nav class="topnav">
  <div class="brand"><span class="mark">H</span><span><?= e(cfg('app_name')) ?></span></div>
  <div class="links">
    <a href="<?= e(url('index.php')) ?>" class="<?= $cur === 'home' ? 'active' : '' ?>"><?= e(t('nav.home')) ?></a>
    <a href="<?= e(url('features.php')) ?>" class="<?= $cur === 'features' ? 'active' : '' ?>"><?= e(t('landing.features_title')) ?></a>
    <a href="<?= e(url('how-it-works.php')) ?>" class="<?= $cur === 'how' ? 'active' : '' ?>"><?= e(t('landing.how_title')) ?></a>
    <a href="<?= e(url('pricing.php')) ?>" class="<?= $cur === 'pricing' ? 'active' : '' ?>"><?= e(t('landing.pricing_title')) ?></a>
    <?= lang_switcher() ?>
    <a class="btn outline small" href="#" onclick="document.getElementById('signinDialog').classList.add('open');return false;" style="margin-left:10px"><?= e(t('common.sign_in')) ?></a>
  </div>
</nav>

<div class="signin-overlay" id="signinDialog" onclick="if(event.target===this)this.classList.remove('open');">
  <div class="signin-dialog" style="position:relative">
    <button class="signin-close" onclick="document.getElementById('signinDialog').classList.remove('open');" aria-label="Close">&times;</button>
    <h2><?= e(t('landing.choose_login')) ?></h2>
    <div class="signin-choice">
      <a class="signin-card" href="<?= e(url('login.php?as=staff')) ?>">
        <div class="ic"><?= icon('id') ?></div>
        <h3><?= e(t('landing.staff_login')) ?></h3>
        <p><?= e(t('landing.staff_login_sub')) ?></p>
      </a>
      <a class="signin-card" href="<?= e(url('login.php?as=patient')) ?>">
        <div class="ic"><?= icon('heart') ?></div>
        <h3><?= e(t('landing.patient_login')) ?></h3>
        <p><?= e(t('landing.patient_login_sub')) ?></p>
      </a>
    </div>
  </div>
</div>
