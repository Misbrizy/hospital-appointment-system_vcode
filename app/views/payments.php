<div class="section-head"><h2>Pending payments <span class="muted">(<?= count($pending) ?>)</span></h2></div>
<?php if (!$pending): ?><div class="card empty">Nothing pending.</div><?php endif; ?>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Hospital</th><th>Plan</th><th>Amount</th><th>Method</th><th>Reference</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($pending as $p): ?>
    <tr>
      <td><?= e($p['hospital_name']) ?></td><td><?= e($p['plan_name']) ?> (<?= e($p['period']) ?>)</td><td><?= money($p['amount']) ?></td><td><?= e($p['method']) ?></td><td><?= e($p['reference'] ?: '—') ?></td>
      <td>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn small" name="decision" value="confirm" type="submit">Confirm</button></form>
        <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn small danger" name="decision" value="reject" type="submit">Reject</button></form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
