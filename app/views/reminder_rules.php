<div class="section-head"><h2>Reminder rules</h2></div>
<p class="muted">These control when SMS reminders go out before an appointment. New bookings use whichever rules are active by default.</p>
<div class="table-wrap" style="margin-bottom:22px"><table class="list">
  <thead><tr><th>Label</th><th>Timing</th><th>Template</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rules as $r): ?>
    <tr>
      <td><?= e($r['label']) ?></td>
      <td><?= (int) $r['days_before'] === 0 ? 'On the day' : (int) $r['days_before'] . ' day(s) before' ?>, <?= e(fmt_time($r['send_time'])) ?></td>
      <td class="mono"><?= e($r['template_code']) ?></td>
      <td><?= $r['is_active'] ? badge('active') : badge('cancelled', 'Off') ?></td>
      <td>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn small ghost" type="submit"><?= $r['is_active'] ? 'Turn off' : 'Turn on' ?></button></form>
        <form method="post" style="display:inline" onsubmit="return confirm('Remove this rule?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn small danger" type="submit">Delete</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<div class="card" style="max-width:520px">
<h3>Add a rule</h3>
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="field"><label>Label</label><input name="label" required placeholder="e.g. 2 days before"></div>
  <div class="row2">
    <div class="field"><label>Days before (0 = same day)</label><input type="number" min="0" max="30" name="days_before" required value="1"></div>
    <div class="field"><label>Send time</label><input type="time" name="send_time" required value="08:00"></div>
  </div>
  <div class="field"><label>Template</label><select name="template_code">
    <?php foreach ($templateCodes as $t): ?><option value="<?= e($t['code']) ?>"><?= e($t['code']) ?></option><?php endforeach; ?>
  </select></div>
  <button class="btn" type="submit">Add rule</button>
</form>
</div>
