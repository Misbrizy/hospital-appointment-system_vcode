<?php
$total = (int) $invoice['amount']; $paid = (int) $invoice['amount_paid']; $balance = max(0, $total - $paid);
$status = $invoice['status'];
$statusLabel = ['paid' => 'PAID', 'partial' => 'PART PAID', 'unpaid' => 'UNPAID', 'cancelled' => 'CANCELLED'][$status] ?? strtoupper($status);
$cur = cfg('currency', 'RWF');
$n = fn($v) => number_format((float) $v);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoice <?= e($invoice['invoice_no']) ?></title>
<style>
  @page { size: A4; margin: 14mm 14mm 16mm; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #e9edf0; color: #17212b; font: 13px/1.45 "Segoe UI", system-ui, Arial, sans-serif; }
  .bar { max-width: 210mm; margin: 14px auto 0; display: flex; gap: 10px; justify-content: flex-end; }
  .bar button, .bar a { font: inherit; padding: 9px 18px; border-radius: 6px; border: 1px solid #0b6e5f; background: #0b6e5f; color: #fff; cursor: pointer; text-decoration: none; }
  .bar a { background: #fff; color: #0b6e5f; }
  .sheet { width: 210mm; min-height: 297mm; margin: 12px auto 30px; background: #fff; padding: 14mm; box-shadow: 0 2px 14px rgba(0,0,0,.15); position: relative; }
  header { display: flex; justify-content: space-between; gap: 20px; border-bottom: 3px solid #0b6e5f; padding-bottom: 12px; }
  h1 { margin: 0; font-size: 22px; color: #0b6e5f; }
  .hosp small { display: block; color: #4b5b68; }
  .doc { text-align: right; }
  .doc .title { font-size: 26px; letter-spacing: 2px; font-weight: 700; color: #0b6e5f; }
  .doc table { margin-left: auto; font-size: 12.5px; }
  .doc td { padding: 1px 0 1px 12px; }
  .parties { display: flex; gap: 20px; margin: 16px 0; }
  .parties > div { flex: 1; border: 1px solid #d5dde3; border-radius: 6px; padding: 10px 12px; }
  .parties h2 { margin: 0 0 4px; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #5b6b78; }
  table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
  table.items th { background: #0b6e5f; color: #fff; text-align: left; padding: 7px 8px; font-size: 12px; }
  table.items td { padding: 7px 8px; border-bottom: 1px solid #e3e9ed; vertical-align: top; }
  .r { text-align: right; } .c { text-align: center; }
  .totals { width: 82mm; margin: 14px 0 0 auto; border-collapse: collapse; }
  .totals td { padding: 5px 8px; }
  .totals tr.grand td { border-top: 2px solid #17212b; font-weight: 700; font-size: 15px; }
  .totals tr.bal td { background: #f1f6f5; font-weight: 700; }
  .stamp { position: absolute; right: 18mm; top: 92mm; transform: rotate(-12deg); border: 3px solid; padding: 4px 14px; font-size: 24px; font-weight: 800; letter-spacing: 2px; opacity: .22; }
  .stamp.paid { color: #0a7a3d; } .stamp.partial { color: #b26a00; } .stamp.unpaid, .stamp.cancelled { color: #b3261e; }
  h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #5b6b78; margin: 20px 0 4px; }
  table.pay { width: 100%; border-collapse: collapse; font-size: 12px; }
  table.pay td, table.pay th { padding: 4px 8px; border-bottom: 1px solid #e3e9ed; text-align: left; }
  .sign { display: flex; gap: 30px; margin-top: 38px; } .sign div { flex: 1; border-top: 1px solid #17212b; padding-top: 4px; font-size: 11.5px; color: #4b5b68; }
  footer { margin-top: 22px; font-size: 11px; color: #5b6b78; border-top: 1px solid #d5dde3; padding-top: 8px; display: flex; justify-content: space-between; }
  @media print {
    body { background: #fff; } .bar { display: none; }
    .sheet { margin: 0; padding: 0; width: auto; min-height: 0; box-shadow: none; }
    .stamp { top: 80mm; } tr { page-break-inside: avoid; } thead { display: table-header-group; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style></head>
<body>
<div class="bar"><a href="<?= e(url('index.php?page=invoice_view&id=' . $invoice['id'])) ?>">← Back to invoice</a><button type="button" onclick="window.print()">Print Invoice</button></div>
<div class="sheet">
  <?php if (in_array($status, ['paid', 'partial', 'unpaid', 'cancelled'], true)): ?><div class="stamp <?= e($status) ?>"><?= e($statusLabel) ?></div><?php endif; ?>
  <header>
    <div class="hosp">
      <h1><?= e($hospital['name']) ?></h1>
      <?php if (!empty($hospital['address'])): ?><small><?= e($hospital['address']) ?><?= !empty($hospital['district']) ? ', ' . e($hospital['district']) : '' ?></small><?php elseif (!empty($hospital['district'])): ?><small><?= e($hospital['district']) ?></small><?php endif; ?>
      <?php if (!empty($hospital['phone'])): ?><small>Tel: <?= e($hospital['phone']) ?></small><?php endif; ?>
      <?php if (!empty($hospital['email'])): ?><small>Email: <?= e($hospital['email']) ?></small><?php endif; ?>
    </div>
    <div class="doc">
      <div class="title">INVOICE</div>
      <table>
        <tr><td>Invoice no.</td><td><strong><?= e($invoice['invoice_no']) ?></strong></td></tr>
        <tr><td>Invoice date</td><td><?= e(fmt_date($invoice['created_at'])) ?></td></tr>
        <?php if ($invoice['due_date']): ?><tr><td>Due date</td><td><?= e(fmt_date($invoice['due_date'])) ?></td></tr><?php endif; ?>
        <tr><td>Payment status</td><td><strong><?= e($statusLabel) ?></strong></td></tr>
      </table>
    </div>
  </header>

  <div class="parties">
    <div>
      <h2>Bill to</h2>
      <strong><?= e(full_name($patient)) ?></strong><br>
      Patient ID: <?= e($patient['patient_no']) ?><br>
      <?php if ($patient['phone']): ?>Tel: <?= e($patient['phone']) ?><br><?php endif; ?>
      <?php if ($patient['address']): ?><?= e($patient['address']) ?><br><?php endif; ?>
      <?php if ($patient['dob']): ?>Date of birth: <?= e(fmt_date($patient['dob'])) ?><?php endif; ?>
    </div>
    <div>
      <h2>Insurance</h2>
      <?= $patient['insurance_provider'] ? e($patient['insurance_provider']) . ($patient['insurance_number'] ? '<br>No: ' . e($patient['insurance_number']) : '') : 'Self-pay / none on file' ?>
    </div>
  </div>

  <table class="items">
    <thead><tr><th style="width:6%">#</th><th>Description of service / treatment</th><th class="c" style="width:9%">Qty</th><th class="r" style="width:19%">Unit price (<?= e($cur) ?>)</th><th class="r" style="width:19%">Amount (<?= e($cur) ?>)</th></tr></thead>
    <tbody>
    <?php foreach ($lines as $i => $l): ?>
      <tr><td><?= $i + 1 ?></td><td><?= e($l['description']) ?></td><td class="c"><?= (int) $l['quantity'] ?></td><td class="r"><?= $n($l['unit_price']) ?></td><td class="r"><?= $n($l['line_total']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <table class="totals">
    <tr><td>Subtotal</td><td class="r"><?= $n($invoice['subtotal'] ?: $invoice['amount']) ?></td></tr>
    <?php if ((int) $invoice['tax_amount']): ?><tr><td>Tax (<?= e(rtrim(rtrim(number_format((float) $invoice['tax_rate'], 2), '0'), '.')) ?>%)</td><td class="r"><?= $n($invoice['tax_amount']) ?></td></tr><?php endif; ?>
    <?php if ((int) $invoice['other_charges']): ?><tr><td><?= e($invoice['other_charges_label'] ?: 'Additional charges') ?></td><td class="r"><?= $n($invoice['other_charges']) ?></td></tr><?php endif; ?>
    <tr class="grand"><td>Total (<?= e($cur) ?>)</td><td class="r"><?= $n($total) ?></td></tr>
    <tr><td>Amount paid</td><td class="r"><?= $n($paid) ?></td></tr>
    <tr class="bal"><td>Outstanding balance</td><td class="r"><?= $status === 'cancelled' ? '—' : $n($balance) ?></td></tr>
  </table>

  <?php if ($payments): ?>
  <h3>Payments received</h3>
  <table class="pay"><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="r">Amount (<?= e($cur) ?>)</th></tr></thead><tbody>
    <?php foreach ($payments as $p): ?><tr><td><?= e(fmt_dt($p['created_at'])) ?></td><td><?= e(ucwords(str_replace('_', ' ', $p['method']))) ?></td><td><?= e($p['reference'] ?: '—') ?></td><td class="r"><?= $n($p['amount']) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <?php endif; ?>

  <div class="sign"><div>Prepared by<?= $issuedBy ? ': ' . e($issuedBy) : '' ?></div><div>Authorised signature &amp; stamp</div></div>
  <footer><span>Thank you for choosing <?= e($hospital['name']) ?>.</span><span>Printed <?= e(fmt_dt(now())) ?> by <?= e($u['name']) ?></span></footer>
</div>
<?php if (get('autoprint') === '1'): ?><script>window.addEventListener('load',function(){window.print();});</script><?php endif; ?>
</body></html>
