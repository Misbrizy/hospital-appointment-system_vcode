<div class="section-head"><h2>Hospitals <span class="muted">(<?= count($hospitals) ?>)</span></h2></div>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Hospital</th><th>Plan</th><th>Patients</th><th>Staff</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($hospitals as $h): ?>
    <tr>
      <td><?= e($h['name']) ?><?= $h['district'] ? ' · ' . e($h['district']) : '' ?></td>
      <td><?= e($h['plan_name']) ?><?= $h['is_trial'] ? ' (trial)' : '' ?></td>
      <td><?= (int) $h['patient_count'] ?></td>
      <td><?= (int) $h['staff_count'] ?></td>
      <td><?= $h['status'] === 'active' ? badge('active') : badge('suspended') ?></td>
      <td><form method="post" onsubmit="return confirm('<?= $h['status'] === 'active' ? 'Suspend' : 'Reactivate' ?> this hospital?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $h['id'] ?>"><button class="btn small <?= $h['status'] === 'active' ? 'danger' : '' ?>" type="submit"><?= $h['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
