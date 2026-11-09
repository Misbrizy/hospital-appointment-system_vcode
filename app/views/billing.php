<div class="section-head"><h2>Billing &amp; plan</h2></div>
<div class="card" style="margin-bottom:20px">
  <h3>Current plan: <?= e($hospital['plan_name']) ?><?= $hospital['is_trial'] ? ' (trial)' : '' ?></h3>
  <p class="muted"><?= $hospital['subscription_ends_at'] ? ($hospital['is_trial'] ? 'Trial ends ' : 'Renews/expires ') . e(fmt_date($hospital['subscription_ends_at'])) : '' ?></p>
  <div class="grid stat-grid">
    <div class="stat"><span class="n"><?= $usage['patients'] ?><?= $usage['max_patients'] ? '/' . $usage['max_patients'] : '' ?></span><span class="l">Patients</span></div>
    <div class="stat"><span class="n"><?= $usage['sms_month'] ?><?= $usage['max_sms_month'] ? '/' . $usage['max_sms_month'] : '' ?></span><span class="l">SMS this month</span></div>
  </div>
</div>

<div class="section-head"><h3>Change plan</h3></div>
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<div class="pricing" style="margin-bottom:24px">
<?php foreach ($plans as $p): ?>
  <div class="plan <?= $p['id'] == $hospital['plan_id'] ? 'pop' : '' ?>">
    <?php if ($p['id'] == $hospital['plan_id']): ?><span class="tag">Current</span><?php endif; ?>
    <h3><?= e($p['name']) ?></h3>
    <div class="price"><?= $p['price_month'] > 0 ? money($p['price_month']) . '/mo' : 'Contact us' ?></div>
    <ul>
      <li><?= $p['max_patients'] ? number_format((int) $p['max_patients']) . ' patients' : 'Unlimited patients' ?></li>
      <li><?= $p['max_sms_month'] ? number_format((int) $p['max_sms_month']) . ' SMS/mo' : 'Unlimited SMS' ?></li>
    </ul>
    <?php if ($p['id'] == $hospital['plan_id']): ?>
      <!-- current plan: nothing to request -->
    <?php elseif ($p['code'] === 'enterprise'): ?>
    <details>
      <summary class="btn block ghost" style="cursor:pointer;text-align:center">Request Enterprise</summary>
      <form method="post" style="margin-top:12px">
        <?= csrf_field() ?><input type="hidden" name="form" value="enterprise">
        <div class="field"><label>What does your hospital need?</label><textarea name="message" rows="3" required placeholder="Number of branches, approximate patients/month, any custom requirements..."><?= pv('message') ?></textarea></div>
        <div class="field"><label>Phone (optional)</label><input name="phone" type="tel" value="<?= pv('phone') ?>"></div>
        <button class="btn block" type="submit">Send request</button>
      </form>
    </details>
    <?php else: ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
      <div class="field"><label>Billing period</label><select name="period"><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></div>
      <div class="field"><label>Payment method</label><select name="method">
        <option value="mtn_momo">MTN Mobile Money</option><option value="airtel_money">Airtel Money</option><option value="bank">Bank transfer</option><option value="cash">Cash</option>
      </select></div>
      <div class="field"><label>Reference / transaction ID (optional)</label><input name="reference"></div>
      <button class="btn block" type="submit">Request <?= $p['sort_order'] < $hospital['plan_sort_order'] ? 'downgrade' : 'upgrade' ?></button>
    </form>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<?php if ($enterpriseRequests): ?>
<div class="section-head"><h3>Your Enterprise requests</h3></div>
<div class="table-wrap" style="margin-bottom:24px"><table class="list">
  <thead><tr><th>Date</th><th>Message</th><th>Status</th></tr></thead>
  <tbody><?php foreach ($enterpriseRequests as $r): ?>
    <tr><td><?= e(fmt_dt($r['created_at'])) ?></td><td><?= e(mb_strimwidth($r['message'], 0, 80, '…')) ?></td><td><?= badge($r['status']) ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div>
<?php endif; ?>

<div class="section-head"><h3>Payment history</h3></div>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$payments): ?><tr><td colspan="5" class="empty">No payments yet.</td></tr><?php endif; ?>
  <?php foreach ($payments as $pay): ?>
    <tr><td><?= e(fmt_dt($pay['created_at'])) ?></td><td><?= (int) $pay['plan_id'] ?></td><td><?= money($pay['amount']) ?></td><td><?= e($pay['method']) ?></td><td><?= badge($pay['status']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
