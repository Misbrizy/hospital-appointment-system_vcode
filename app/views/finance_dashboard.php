<div class="grid stat-grid" style="margin-bottom:22px">
  <div class="stat leaf"><span class="n"><?= money($stats['revenue_month']) ?></span><span class="l">Collected this month</span></div>
  <div class="stat amber"><span class="n"><?= money($stats['outstanding']) ?></span><span class="l">Outstanding (<?= $stats['unpaid_count'] ?> invoices)</span></div>
  <div class="stat danger"><span class="n"><?= money($stats['expenses_month']) ?></span><span class="l">Expenses this month</span></div>
  <div class="stat <?= $stats['net_month'] >= 0 ? 'leaf' : 'danger' ?>"><span class="n"><?= money($stats['net_month']) ?></span><span class="l">Net this month</span></div>
  <div class="stat"><span class="n"><?= $stats['invoices_month'] ?></span><span class="l">Invoices this month</span></div>
</div>

<div class="section-head"><h2>Recent invoices</h2>
  <div style="display:flex;gap:8px">
    <a class="btn small" href="<?= e(url('index.php?page=invoice_new')) ?>"><?= icon('plus') ?> New invoice</a>
    <a class="btn small ghost" href="<?= e(url('index.php?page=invoices')) ?>">View all</a>
  </div>
</div>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Invoice</th><th>Patient</th><th>Amount</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$recentInvoices): ?><tr><td colspan="4" class="empty">No invoices yet.</td></tr><?php endif; ?>
  <?php foreach ($recentInvoices as $i): ?>
    <tr>
      <td><a href="<?= e(url('index.php?page=invoice_view&id=' . $i['id'])) ?>" class="mono"><?= e($i['invoice_no']) ?></a></td>
      <td><?= e($i['first_name'] . ' ' . $i['last_name']) ?></td>
      <td><?= money($i['amount']) ?></td>
      <td><?= badge($i['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
