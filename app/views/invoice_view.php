<div class="section-head">
  <h2>Invoice <?= e($invoice['invoice_no']) ?></h2>
  <div style="display:flex;gap:10px;align-items:center">
    <?= badge($invoice['status']) ?>
    <a class="btn" target="_blank" rel="noopener" href="<?= e(url('index.php?page=invoice_print&id=' . $invoice['id'])) ?>"><?= icon('send') ?> Print Invoice</a>
  </div>
</div>
<div class="grid" style="grid-template-columns:2fr 1fr;align-items:start">
<div>
  <div class="card">
    <dl class="kv">
      <dt>Patient</dt><dd><a href="<?= e(url('index.php?page=patient_view&id=' . $patient['id'])) ?>"><?= e(full_name($patient)) ?></a></dd>
      <dt>Subtotal</dt><dd><?= money($invoice['subtotal'] ?: $invoice['amount']) ?></dd>
      <?php if ((int) $invoice['tax_amount']): ?><dt>Tax (<?= e(rtrim(rtrim(number_format((float) $invoice['tax_rate'], 2), '0'), '.')) ?>%)</dt><dd><?= money($invoice['tax_amount']) ?></dd><?php endif; ?>
      <?php if ((int) $invoice['other_charges']): ?><dt><?= e($invoice['other_charges_label'] ?: 'Additional charges') ?></dt><dd><?= money($invoice['other_charges']) ?></dd><?php endif; ?>
      <dt>Total</dt><dd><strong><?= money($invoice['amount']) ?></strong></dd>
      <dt>Paid</dt><dd><?= money($invoice['amount_paid']) ?></dd>
      <dt>Remaining</dt><dd><?= money($invoice['amount'] - $invoice['amount_paid']) ?></dd>
      <dt>Due date</dt><dd><?= $invoice['due_date'] ? e(fmt_date($invoice['due_date'])) : '—' ?></dd>
      <dt>Created</dt><dd><?= e(fmt_dt($invoice['created_at'])) ?></dd>
    </dl>
  </div>

  <div class="card" style="margin-top:16px">
    <h3>Services &amp; treatments</h3>
    <div class="table-wrap"><table class="list">
      <thead><tr><th>Description</th><th>Qty</th><th>Unit price</th><th style="text-align:right">Total</th></tr></thead>
      <tbody><?php foreach ($lines as $l): ?><tr><td><?= e($l['description']) ?></td><td><?= (int) $l['quantity'] ?></td><td><?= money($l['unit_price']) ?></td><td style="text-align:right"><?= money($l['line_total']) ?></td></tr><?php endforeach; ?></tbody>
    </table></div>
  </div>

  <div class="card" style="margin-top:16px">
    <h3>Payment history</h3>
    <?php if (!$payments): ?><p class="empty">No payments recorded yet.</p><?php else: ?>
    <div class="table-wrap"><table class="list">
      <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
      <tbody><?php foreach ($payments as $p): ?>
        <tr><td><?= e(fmt_dt($p['created_at'])) ?></td><td><?= money($p['amount']) ?></td><td><?= e(ucfirst(str_replace('_', ' ', $p['method']))) ?></td><td><?= e($p['reference'] ?: '—') ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
  </div>
</div>

<div>
  <?php if (can('finance.write') && in_array($invoice['status'], ['unpaid', 'partial'], true)): ?>
  <div class="card">
    <h3>Record a payment</h3>
    <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="pay">
      <div class="field"><label>Amount (RWF)</label><input type="number" name="amount" min="1" max="<?= $invoice['amount'] - $invoice['amount_paid'] ?>" required value="<?= pv('amount', $invoice['amount'] - $invoice['amount_paid']) ?>"></div>
      <div class="field"><label>Method</label><select name="method">
        <option value="cash">Cash</option><option value="mtn_momo">MTN Mobile Money</option><option value="airtel_money">Airtel Money</option><option value="bank">Bank transfer</option><option value="insurance">Insurance</option>
      </select></div>
      <div class="field"><label>Reference (optional)</label><input name="reference"></div>
      <button class="btn block" type="submit">Record payment</button>
    </form>
  </div>
  <form method="post" style="margin-top:12px" onsubmit="return confirm('Cancel this invoice?')">
    <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
    <button class="btn danger small block" type="submit">Cancel invoice</button>
  </form>
  <?php endif; ?>
</div>
</div>
