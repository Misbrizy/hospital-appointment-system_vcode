<div class="section-head"><h2>Hospital settings</h2></div>
<div class="card" style="max-width:560px">
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="field"><label>Hospital name</label><input name="name" required value="<?= pv('name', $hospital['name']) ?>"></div>
  <div class="row2">
    <div class="field"><label>Phone</label><input name="phone" value="<?= pv('phone', $hospital['phone'] ?? '') ?>"></div>
    <div class="field"><label>Email</label><input name="email" type="email" value="<?= pv('email', $hospital['email'] ?? '') ?>"></div>
  </div>
  <div class="field"><label>Address</label><input name="address" value="<?= pv('address', $hospital['address'] ?? '') ?>"></div>
  <div class="field"><label>District</label><input name="district" value="<?= pv('district', $hospital['district'] ?? '') ?>"></div>
  <button class="btn" type="submit">Save</button>
</form>
</div>
<div class="card" style="max-width:560px;margin-top:18px">
  <h3>SMS &amp; templates</h3>
  <p class="muted">Manage message wording in <a href="<?= e(url('index.php?page=sms_templates')) ?>">SMS templates</a> and timing in <a href="<?= e(url('index.php?page=reminder_rules')) ?>">Reminder rules</a>.</p>
</div>

<?php if ($testResult): ?>
  <div class="alert <?= $testResult['ok'] ? 'success' : 'error' ?>" style="max-width:560px;margin-top:18px"><?= e($testResult['msg']) ?></div>
<?php endif; ?>

<div class="card" style="max-width:560px;margin-top:18px">
  <h3>Email reminders (SMTP)</h3>
  <p class="muted">Needed for patients who opted into email reminders. Use your own mailbox's SMTP details — most providers (Gmail, Outlook, Zoho) give you an "app password" for this.</p>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="form" value="smtp">
    <div class="row2">
      <div class="field"><label>SMTP host</label><input name="smtp_host" placeholder="smtp.gmail.com" value="<?= pv('smtp_host', $smtp['host'] ?? '') ?>"></div>
      <div class="field"><label>Port</label><input name="smtp_port" type="number" placeholder="587" value="<?= pv('smtp_port', $smtp['port'] ?? '587') ?>"></div>
    </div>
    <div class="field"><label>Encryption</label><select name="smtp_encryption">
      <option value="tls" <?= selected($smtp['encryption'] ?? 'tls', 'tls') ?>>STARTTLS (port 587)</option>
      <option value="ssl" <?= selected($smtp['encryption'] ?? 'tls', 'ssl') ?>>SSL/TLS (port 465)</option>
    </select></div>
    <div class="row2">
      <div class="field"><label>Username</label><input name="smtp_username" value="<?= pv('smtp_username', $smtp['username'] ?? '') ?>"></div>
      <div class="field"><label>Password <?= $smtp && $smtp['password'] ? '(set — leave blank to keep)' : '' ?></label><input name="smtp_password" type="password"></div>
    </div>
    <div class="row2">
      <div class="field"><label>From email</label><input name="smtp_from_email" type="email" value="<?= pv('smtp_from_email', $smtp['from_email'] ?? '') ?>"></div>
      <div class="field"><label>From name</label><input name="smtp_from_name" value="<?= pv('smtp_from_name', $smtp['from_name'] ?? $hospital['name']) ?>"></div>
    </div>
    <button class="btn small" type="submit">Save email settings</button>
  </form>
  <?php if ($smtp): ?>
  <form method="post" style="margin-top:14px;border-top:1px solid var(--line-soft);padding-top:14px">
    <?= csrf_field() ?><input type="hidden" name="form" value="test_email">
    <div class="field"><label>Send a test to</label><input name="test_email_to" type="email" placeholder="you@example.com" required></div>
    <button class="btn small ghost" type="submit">Send test email</button>
  </form>
  <?php endif; ?>
</div>

<div class="card" style="max-width:560px;margin-top:18px">
  <h3>WhatsApp reminders (Twilio)</h3>
  <p class="muted">Free to try: create a Twilio account, open <em>Messaging → Try it out → Send a WhatsApp message</em>, and send "join &lt;your-code&gt;" from your phone to their sandbox number. Enter the sandbox details below — no payment needed to test.</p>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="form" value="whatsapp">
    <div class="field"><label>Twilio Account SID</label><input name="wa_twilio_sid" value="<?= pv('wa_twilio_sid', $wa['twilio_sid'] ?? '') ?>"></div>
    <div class="field"><label>Twilio Auth Token <?= $wa && $wa['twilio_token'] ? '(set — leave blank to keep)' : '' ?></label><input name="wa_twilio_token" type="password"></div>
    <div class="field"><label>WhatsApp sender number</label><input name="wa_twilio_from" placeholder="+14155238886" value="<?= pv('wa_twilio_from', $wa['twilio_whatsapp_from'] ?? '') ?>"><p class="hint">The Twilio sandbox number shown on their WhatsApp sandbox page.</p></div>
    <button class="btn small" type="submit">Save WhatsApp settings</button>
  </form>
  <?php if ($wa): ?>
  <form method="post" style="margin-top:14px;border-top:1px solid var(--line-soft);padding-top:14px">
    <?= csrf_field() ?><input type="hidden" name="form" value="test_whatsapp">
    <div class="field"><label>Send a test to</label><input name="test_wa_to" type="tel" placeholder="07XXXXXXXX" required><p class="hint">Must be a number that has joined your Twilio sandbox.</p></div>
    <button class="btn small ghost" type="submit">Send test WhatsApp</button>
  </form>
  <?php endif; ?>
</div>
