<div class="section-head"><h2>SMS templates</h2></div>
<p class="muted">Use <code>{patient_name}</code>, <code>{hospital_name}</code>, <code>{appointment_date}</code>, <code>{appointment_time}</code> — they're replaced automatically when a message is sent.</p>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Name</th><th>Code</th><th>Language</th><th>Preview</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($templates as $t): ?>
    <tr>
      <td><?= e($t['name']) ?></td><td class="mono"><?= e($t['code']) ?></td><td><?= $t['language'] === 'rw' ? 'Kinyarwanda' : 'English' ?></td>
      <td><?= e(mb_strimwidth($t['body'], 0, 60, '…')) ?></td>
      <td><a href="<?= e(url('index.php?page=sms_template_edit&id=' . $t['id'])) ?>">Edit</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
