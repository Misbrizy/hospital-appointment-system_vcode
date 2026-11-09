<div class="section-head"><h2>Staff</h2><a class="btn" href="<?= e(url('index.php?page=user_new')) ?>"><?= icon('plus') ?> Add staff member</a></div>

<?php if ($pending): ?>
<div class="section-head"><h3 style="margin:0">Bategereje kwemezwa <span class="muted">(<?= count($pending) ?>)</span></h3></div>
<div class="table-wrap" style="margin-bottom:24px"><table class="list">
  <thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Phone</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($pending as $p): ?>
    <tr>
      <td><?= e($p['name']) ?></td><td><?= e(role_label($p['role'])) ?><?= $p['specialty'] ? ' · ' . e($p['specialty']) : '' ?></td>
      <td><?= e($p['email'] ?: '—') ?></td><td><?= e($p['phone'] ?: '—') ?></td>
      <td>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $p['id'] ?>">
          <?php if ($p['role'] === 'doctor'): ?><select name="department_id" required aria-label="Department" style="width:auto;display:inline-block"><option value="">Department…</option><?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= (int) $p['department_id'] === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select><?php endif; ?>
          <button class="btn small" type="submit">Emeza</button></form>
        <a class="btn small ghost" href="<?= e(url('index.php?page=user_edit&id=' . $p['id'])) ?>">Reba</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>

<div class="table-wrap"><table class="list">
  <thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Phone</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($staff as $s): if ($s['role'] === 'patient') continue; ?>
    <tr>
      <td><?= e($s['name']) ?></td><td><?= e(role_label($s['role'])) ?><?= $s['specialty'] ? ' · ' . e($s['specialty']) : '' ?></td>
      <td><?= e($s['email'] ?: '—') ?></td><td><?= e($s['phone'] ?: '—') ?></td>
      <td><?= $s['is_active'] ? badge('active') : badge('suspended', 'Bategereje/Disabled') ?></td>
      <td><a href="<?= e(url('index.php?page=user_edit&id=' . $s['id'])) ?>">Edit</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
