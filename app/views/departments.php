<div class="section-head"><h2>Departments</h2></div>
<div class="table-wrap" style="margin-bottom:22px;max-width:500px"><table class="list">
  <thead><tr><th>Name</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($departments as $d): ?>
    <tr><td><?= e($d['name']) ?></td><td><?= $d['is_active'] ? badge('active') : badge('suspended', 'Inactive') ?></td>
    <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $d['id'] ?>"><button class="btn small ghost" type="submit"><?= $d['is_active'] ? 'Deactivate' : 'Activate' ?></button></form></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<div class="card" style="max-width:360px">
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="field"><label>New department</label><input name="name" required></div>
  <button class="btn" type="submit">Add</button>
</form>
</div>
