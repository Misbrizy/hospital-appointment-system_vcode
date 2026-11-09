<div class="section-head"><h2>Expenses</h2></div>
<form method="get" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;align-items:end">
  <input type="hidden" name="page" value="expenses">
  <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <button class="btn small" type="submit">Update</button>
  <span class="muted" style="margin-left:auto">Total: <strong><?= money($sum) ?></strong></span>
</form>

<div class="grid" style="grid-template-columns:2fr 1fr;align-items:start">
<div>
  <div class="table-wrap"><table class="list">
    <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Recorded by</th></tr></thead>
    <tbody>
    <?php if (!$expenses): ?><tr><td colspan="5" class="empty">No expenses in this range.</td></tr><?php endif; ?>
    <?php foreach ($expenses as $e): ?>
      <tr><td><?= e(fmt_date($e['expense_date'])) ?></td><td><?= e($e['category']) ?></td><td><?= e($e['description'] ?: '—') ?></td><td><?= money($e['amount']) ?></td><td><?= e($e['recorded_by_name'] ?? '—') ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?= pager($p) ?>
</div>
<div>
  <?php if (can('finance.write')): ?>
  <div class="card">
    <h3>Add expense</h3>
    <form method="post">
      <?= csrf_field() ?>
      <div class="field"><label>Category</label><input name="category" required list="expense-cats" placeholder="e.g. Supplies, Utilities, Salaries"></div>
      <datalist id="expense-cats"><option>Medical supplies</option><option>Utilities</option><option>Salaries</option><option>Maintenance</option><option>Transport</option><option>Other</option></datalist>
      <div class="field"><label>Description (optional)</label><input name="description"></div>
      <div class="field"><label>Amount (RWF)</label><input type="number" name="amount" min="1" required></div>
      <div class="field"><label>Date</label><input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required></div>
      <button class="btn block" type="submit">Add expense</button>
    </form>
  </div>
  <?php endif; ?>
</div>
</div>
