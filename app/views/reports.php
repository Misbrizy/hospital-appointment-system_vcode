<div class="section-head"><h2>Reports</h2></div>

<div class="card no-print" style="max-width:420px;margin-bottom:20px">
  <h3 style="margin-top:0">Report for one patient</h3>
  <p class="muted" style="font-size:.85rem">Look up a patient to see and print just their own report — not the whole hospital's.</p>
  <form method="get">
    <input type="hidden" name="page" value="reports">
    <div class="field" style="margin:0"><select name="patient_id" required onchange="this.form.submit()">
      <option value="">— Search a patient —</option>
      <?php foreach ($allPatients as $pt): ?><option value="<?= $pt['id'] ?>"><?= e($pt['first_name'] . ' ' . $pt['last_name']) ?> (<?= e($pt['patient_no']) ?>)</option><?php endforeach; ?>
    </select></div>
  </form>
</div>

<h3>Hospital-wide report</h3>
<form method="get" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;align-items:end" class="no-print">
  <input type="hidden" name="page" value="reports">
  <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <button class="btn small" type="submit">Update</button>
  <button class="btn ghost small" type="button" onclick="window.print()">Print</button>
</form>

<div class="section-head"><h3>Appointments</h3></div>
<div class="grid stat-grid" style="margin-bottom:24px">
  <div class="stat"><span class="n"><?= (int) $byStatus['total'] ?></span><span class="l">Total appointments</span></div>
  <div class="stat leaf"><span class="n"><?= (int) $byStatus['completed'] ?></span><span class="l">Completed</span></div>
  <div class="stat danger"><span class="n"><?= (int) $byStatus['cancelled'] ?></span><span class="l">Cancelled</span></div>
  <div class="stat amber"><span class="n"><?= (int) $byStatus['missed'] ?></span><span class="l">Missed</span></div>
  <div class="stat"><span class="n"><?= (int) $byStatus['upcoming'] ?></span><span class="l">Upcoming</span></div>
</div>

<div class="section-head"><h3>SMS</h3></div>
<div class="grid stat-grid" style="margin-bottom:24px">
  <div class="stat"><span class="n"><?= (int) $sms['total'] ?></span><span class="l">SMS sent</span></div>
  <div class="stat leaf"><span class="n"><?= (int) $sms['delivered'] ?></span><span class="l">Delivered</span></div>
  <div class="stat danger"><span class="n"><?= (int) $sms['failed'] ?></span><span class="l">Failed</span></div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr">
<div class="card">
  <h3>Monthly appointments (last 6 months)</h3>
  <?php $max = max(1, ...array_map(fn($m) => (int) $m['n'], $monthly ?: [['n'=>1]])); ?>
  <?php foreach ($monthly as $m): ?>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
      <span class="muted" style="width:60px;font-size:.82rem"><?= e(date('M Y', strtotime($m['ym'] . '-01'))) ?></span>
      <div style="flex:1;background:var(--line-soft);border-radius:4px;overflow:hidden"><div style="width:<?= max(4, (int) $m['n'] / $max * 100) ?>%;background:var(--teal);height:16px"></div></div>
      <span style="width:30px;text-align:right;font-size:.82rem"><?= (int) $m['n'] ?></span>
    </div>
  <?php endforeach; ?>
  <?php if (!$monthly): ?><p class="empty">No data yet.</p><?php endif; ?>
</div>
<div class="card">
  <h3>By department</h3>
  <table class="list"><tbody>
  <?php foreach ($byDept as $d): ?><tr><td><?= e($d['name']) ?></td><td style="text-align:right"><?= (int) $d['n'] ?></td></tr><?php endforeach; ?>
  <?php if (!$byDept): ?><tr><td class="empty">No data.</td></tr><?php endif; ?>
  </tbody></table>
</div>
</div>

<?php if ($byDoctor): ?>
<div class="card" style="margin-top:20px">
  <h3>By doctor</h3>
  <div class="table-wrap"><table class="list">
    <thead><tr><th>Doctor</th><th>Appointments</th><th>Completed</th><th>Missed</th></tr></thead>
    <tbody><?php foreach ($byDoctor as $d): ?><tr><td><?= e($d['name']) ?></td><td><?= (int) $d['n'] ?></td><td><?= (int) $d['completed'] ?></td><td><?= (int) $d['missed'] ?></td></tr><?php endforeach; ?></tbody>
  </table></div>
</div>
<?php endif; ?>
