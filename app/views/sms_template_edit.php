<div class="card" style="max-width:600px">
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="field"><label>Name</label><input name="name" required value="<?= pv('name', $tpl['name']) ?>"></div>
  <div class="field"><label>Message (<?= $tpl['language'] === 'rw' ? 'Kinyarwanda' : 'English' ?>)</label><textarea name="body" rows="4" required><?= pv('body', $tpl['body']) ?></textarea>
    <p class="hint">Placeholders: <code>{patient_name}</code> <code>{hospital_name}</code> <code>{appointment_date}</code> <code>{appointment_time}</code></p>
  </div>
  <button class="btn" type="submit">Save template</button>
  <a class="btn ghost" href="<?= e(url('index.php?page=sms_templates')) ?>">Cancel</a>
</form>
</div>
