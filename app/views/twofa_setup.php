<div class="card" style="max-width:460px">
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<p>1. Open your authenticator app and add a new account manually.</p>
<p>2. Enter this secret key (or scan below if your app supports it):</p>
<p class="mono" style="background:var(--teal-wash);padding:10px 12px;border-radius:8px;letter-spacing:.1em;font-size:1.05rem"><?= e(chunk_split($secret, 4, ' ')) ?></p>
<p class="muted" style="font-size:.8rem">Account name: <?= e(cfg('app_name')) ?>:<?= e($u['email'] ?: $u['phone']) ?></p>
<p>3. Enter the 6-digit code your app shows now:</p>
<form method="post">
  <?= csrf_field() ?>
  <div class="field"><input name="code" inputmode="numeric" maxlength="6" required autofocus style="text-align:center;font-size:1.3rem;letter-spacing:.3em" placeholder="000000"></div>
  <button class="btn" type="submit">Confirm and turn on</button>
  <a class="btn ghost" href="<?= e(url('index.php?page=account')) ?>">Cancel</a>
</form>
</div>
