<?php if (!empty($u['must_change_password'])): ?><div class="alert warn">Please set a new password before continuing.</div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<div class="grid" style="grid-template-columns:1fr 1fr;align-items:start">
<div class="card">
  <h3>Profile</h3>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="form" value="profile">
    <div class="field"><label>Name</label><input name="name" required value="<?= pv('name', $u['name']) ?>"></div>
    <div class="field"><label>Email</label><input name="email" type="email" value="<?= pv('email', $u['email'] ?? '') ?>"></div>
    <button class="btn" type="submit">Save profile</button>
  </form>
</div>
<div class="card">
  <h3>Change password</h3>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="form" value="password">
    <div class="field"><label>Current password</label><input type="password" name="current_password" required></div>
    <div class="field"><label>New password</label><input type="password" name="new_password" required></div>
    <div class="field"><label>Confirm new password</label><input type="password" name="new_password2" required></div>
    <button class="btn" type="submit">Change password</button>
  </form>
</div>
</div>
<div class="card" style="margin-top:18px;max-width:520px">
  <h3>Two-factor authentication</h3>
  <?php if ($fresh['totp_enabled']): ?>
    <p class="muted">Enabled — your account requires a code from your authenticator app at sign-in.</p>
    <form method="post" onsubmit="return confirm('Turn off two-factor authentication?')"><?= csrf_field() ?><input type="hidden" name="form" value="disable_2fa"><button class="btn danger small" type="submit">Turn off 2FA</button></form>
  <?php else: ?>
    <p class="muted">Add an extra layer of protection using Google Authenticator, Authy or similar.</p>
    <a class="btn small" href="<?= e(url('index.php?page=twofa_setup')) ?>">Set up 2FA</a>
  <?php endif; ?>
</div>
