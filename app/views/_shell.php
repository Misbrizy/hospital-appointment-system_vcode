<?php require_once APP_ROOT . '/app/views/_icon.php'; $page = $_GET['page'] ?? 'dashboard'; $unread = $u ? (int) val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL', [$u['id']]) : 0; ?>
<!doctype html>
<html lang="<?= e(ui_lang()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?> · <?= e(cfg('app_name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <div class="brand"><span class="mark">H</span><span><?= e(cfg('app_name')) ?></span></div>
    <nav>
      <?php foreach ((NAV[$u['role']] ?? []) as $item): if (is_string($item)): ?>
        <div class="group-label"><?= e(t(ltrim($item, '—'))) ?></div>
      <?php else: [$slug, $labelKey, $ic] = $item; ?>
        <a href="<?= e(url('index.php?page=' . $slug)) ?>" class="<?= $page === $slug ? 'active' : '' ?>"><?= icon($ic) ?> <?= e(t($labelKey)) ?></a>
      <?php endif; endforeach; ?>
    </nav>
    <div class="who">
      <span class="name"><?= e($u['name']) ?></span>
      <span class="muted"><?= e(t('role.' . $u['role'])) ?></span>
      <br><a href="<?= e(url('index.php?page=account')) ?>"><?= e(t('nav.my_account')) ?></a> · <a href="<?= e(url('logout.php')) ?>"><?= e(t('nav.sign_out')) ?></a>
      <div style="margin-top:8px"><?= lang_switcher() ?></div>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:10px">
        <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Menu">☰</button>
        <h1><?= e($title) ?></h1>
      </div>
      <div class="actions">
        <a href="<?= e(url('index.php?page=notifications')) ?>" class="btn ghost small <?= $unread ? 'notif-dot' : '' ?>" data-n="<?= $unread ?>"><?= icon('bell') ?></a>
        <?= $opts['topbar'] ?? '' ?>
      </div>
    </header>
    <div class="content">
      <?php foreach (flashes() as $f): ?><div class="alert <?= e($f['t'] === 'error' ? 'error' : ($f['t'] === 'success' ? 'success' : ($f['t'] === 'warn' ? 'warn' : 'info'))) ?>"><?= e($f['m']) ?></div><?php endforeach; ?>
      <?= $body ?>
    </div>
  </div>
</div>
</body>
</html>
