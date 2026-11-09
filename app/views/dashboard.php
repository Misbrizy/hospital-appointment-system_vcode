<?php if (!empty($schedulerStatus) && !$schedulerStatus['healthy']): ?>
<div class="alert error" style="margin-bottom:16px"><strong>Reminders are not being delivered automatically.</strong>
  <?= $schedulerStatus['last_run'] ? 'The scheduler last ran ' . e(fmt_dt(date('Y-m-d H:i:s', $schedulerStatus['last_run']))) . '.' : 'The scheduler has not run yet.' ?>
  Set it up once (Windows: run <code>bin\install-windows-task.bat</code>) or see <a href="<?= e(url('index.php?page=sms_logs')) ?>">Messages</a>.</div>
<?php endif; ?>
<div class="grid stat-grid" style="margin-bottom:22px">
  <div class="stat"><span class="n"><?= $stats['patients'] ?></span><span class="l"><?= e(t('dash.patients')) ?></span></div>
  <div class="stat clay"><span class="n"><?= $stats['today'] ?></span><span class="l"><?= e(t('dash.today_appts')) ?></span></div>
  <div class="stat"><span class="n"><?= $stats['upcoming'] ?></span><span class="l"><?= e(t('dash.upcoming')) ?></span></div>
  <div class="stat danger"><span class="n"><?= $stats['missed'] ?></span><span class="l"><?= e(t('dash.missed')) ?></span></div>
  <div class="stat leaf"><span class="n"><?= $stats['sms_today'] ?></span><span class="l"><?= e(t('dash.sms_today')) ?></span></div>
  <div class="stat amber"><span class="n"><?= $stats['sms_pending'] ?></span><span class="l"><?= e(t('dash.sms_pending')) ?></span></div>
  <div class="stat"><span class="n"><?= $stats['waiting'] ?></span><span class="l"><?= e(t('dash.waiting')) ?></span></div>
  <?php if (can('reschedule.review')): ?>
  <div class="stat clay"><span class="n"><?= $stats['reschedule_pending'] ?></span><span class="l"><?= e(t('dash.reschedule_pending')) ?></span></div>
  <?php endif; ?>
</div>

<?php if (!empty($usage['is_trial']) || ($usage['max_patients'] !== null && $usage['patients'] >= $usage['max_patients'] * 0.85)): ?>
<div class="alert <?= $usage['is_trial'] ? 'info' : 'warn' ?>">
  <?php if ($usage['is_trial']): ?>You're on a free trial of the <?= e($usage['plan_name']) ?> plan<?= $usage['ends_at'] ? ', ending ' . e(fmt_date($usage['ends_at'])) : '' ?>. <a href="<?= e(url('index.php?page=billing')) ?>">View billing</a>.
  <?php else: ?>You're approaching your <?= e($usage['plan_name']) ?> plan's patient limit (<?= $usage['patients'] ?>/<?= (int) $usage['max_patients'] ?>). <a href="<?= e(url('index.php?page=billing')) ?>">Consider upgrading</a>.
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="section-head">
  <h2><?= e(t('dash.todays_schedule')) ?></h2>
  <a class="btn small" href="<?= e(url('index.php?page=appointments')) ?>">View all appointments</a>
</div>
<div class="table-wrap">
<table class="list">
  <thead><tr><th>Time</th><th>Patient</th><th>Type</th><th>Doctor</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$schedule): ?><tr><td colspan="5" class="empty">No appointments scheduled for today.</td></tr><?php endif; ?>
  <?php foreach ($schedule as $a): ?>
    <tr>
      <td><?= e(fmt_time($a['appt_time'])) ?></td>
      <td><a href="<?= e(url('index.php?page=patient_view&id=' . $a['patient_id'])) ?>"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></a></td>
      <td><?= e($a['appt_type']) ?><?= $a['dept_name'] ? ' · ' . e($a['dept_name']) : '' ?></td>
      <td><?= e($a['doctor_name'] ?? '—') ?></td>
      <td><?= badge($a['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
