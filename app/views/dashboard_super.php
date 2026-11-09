<div class="grid stat-grid" style="margin-bottom:22px">
  <div class="stat"><span class="n"><?= $stats['hospitals'] ?></span><span class="l">Active hospitals</span></div>
  <div class="stat amber"><span class="n"><?= $stats['trials'] ?></span><span class="l">On trial</span></div>
  <div class="stat danger"><span class="n"><?= $stats['suspended'] ?></span><span class="l">Suspended</span></div>
  <div class="stat"><span class="n"><?= $stats['patients'] ?></span><span class="l">Total patients</span></div>
  <div class="stat clay"><span class="n"><?= $stats['appointments_today'] ?></span><span class="l">Appointments today</span></div>
  <div class="stat leaf"><span class="n"><?= $stats['sms_today'] ?></span><span class="l">SMS sent today</span></div>
  <div class="stat amber"><span class="n"><?= $stats['payments_pending'] ?></span><span class="l">Payments to confirm</span></div>
  <div class="stat clay"><span class="n"><?= $stats['doctors_pending'] ?></span><span class="l">Doctors pending approval</span></div>
</div>

<?php if ($stats['payments_pending'] > 0): ?>
<div class="alert warn"><?= $stats['payments_pending'] ?> payment(s) are waiting for confirmation. <a href="<?= e(url('index.php?page=payments')) ?>">Review them</a>.</div>
<?php endif; ?>

<div class="section-head"><h2>Recently registered hospitals</h2><a class="btn small" href="<?= e(url('index.php?page=hospitals')) ?>">View all hospitals</a></div>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Hospital</th><th>Plan</th><th>Status</th><th>Registered</th></tr></thead>
  <tbody>
  <?php if (!$recentHospitals): ?><tr><td colspan="4" class="empty">No hospitals yet.</td></tr><?php endif; ?>
  <?php foreach ($recentHospitals as $h): ?>
    <tr>
      <td><?= e($h['name']) ?><?= $h['district'] ? ' · ' . e($h['district']) : '' ?></td>
      <td><?= e($h['plan_name']) ?><?= $h['is_trial'] ? ' (trial)' : '' ?></td>
      <td><?= $h['status'] === 'active' ? badge('active') : badge('suspended') ?></td>
      <td><?= e(fmt_date($h['created_at'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
