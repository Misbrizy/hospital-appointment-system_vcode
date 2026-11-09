<div class="section-head"><h2>Invoices <span class="muted">(<?= $p['total'] ?>)</span></h2>
  <a class="btn" href="<?= e(url('index.php?page=invoice_new')) ?>"><?= icon('plus') ?> New invoice</a>
</div>
<form method="get" style="margin-bottom:14px;max-width:220px">
  <input type="hidden" name="page" value="invoices">
  <select name="status" onchange="this.form.submit()">
    <option value="">All statuses</option>
    <?php foreach (['unpaid','partial','paid','cancelled'] as $s): ?><option value="<?= $s ?>" <?= selected($status, $s) ?>><?= e(t('status.' . $s)) ?></option><?php endforeach; ?>
  </select>
</form>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Invoice</th><th>Patient</th><th>Description</th><th>Amount</th><th>Paid</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$invoices): ?><tr><td colspan="6" class="empty">No invoices found.</td></tr><?php endif; ?>
  <?php foreach ($invoices as $i): ?>
    <tr>
      <td><a href="<?= e(url('index.php?page=invoice_view&id=' . $i['id'])) ?>" class="mono"><?= e($i['invoice_no']) ?></a></td>
      <td><?= e($i['first_name'] . ' ' . $i['last_name']) ?></td>
      <td><?= e(mb_strimwidth($i['description'], 0, 40, '…')) ?></td>
      <td><?= money($i['amount']) ?></td>
      <td><?= money($i['amount_paid']) ?></td>
      <td><?= badge($i['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?= pager($p) ?>
