<div class="section-head">
  <h2>Appointments</h2>
  <?php if (can('appointments.write')): ?><a class="btn" href="<?= e(url('index.php?page=appointment_new')) ?>"><?= icon('plus') ?> Book appointment</a><?php endif; ?>
</div>
<form method="get" style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:end">
  <input type="hidden" name="page" value="appointments">
  <div class="field" style="margin:0"><label>Date</label><input type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()"></div>
  <div class="field" style="margin:0"><label>Status</label><select name="status" onchange="this.form.submit()">
    <option value="">All</option>
    <?php foreach (['scheduled','confirmed','checked_in','in_consultation','completed','cancelled','missed'] as $s): ?>
      <option value="<?= $s ?>" <?= selected($status, $s) ?>><?= e(STATUS_LABELS[$s]) ?></option>
    <?php endforeach; ?>
  </select></div>
</form>
<div class="table-wrap">
<table class="list">
  <thead><tr><th>Time</th><th>Patient</th><th>Type</th><th>Department</th><th>Doctor</th><th>Status</th><th>Reschedule requested</th><th></th></tr></thead>
  <tbody>
  <?php if (!$appts): ?><tr><td colspan="8" class="empty">No appointments on this day.</td></tr><?php endif; ?>
  <?php foreach ($appts as $a): ?>
    <tr>
      <td><?= e(fmt_time($a['appt_time'])) ?></td>
      <td><a href="<?= e(url('index.php?page=patient_view&id=' . $a['patient_id'])) ?>"><?= e($a['first_name'] . ' ' . $a['last_name']) ?></a></td>
      <td><?= e($a['appt_type']) ?></td>
      <td><?= e($a['dept_name'] ?? '—') ?></td>
      <td><?= e($a['doctor_name'] ?? '—') ?></td>
      <td><?= badge($a['status']) ?></td>
      <td><?php if ($a['pending_requested_date']): ?><span class="badge s-pending"><?= e(fmt_date($a['pending_requested_date'])) ?><?= $a['pending_requested_time'] ? ' ' . e(fmt_time($a['pending_requested_time'])) : '' ?></span><?php else: ?>—<?php endif; ?></td>
      <td><?php if (can('appointments.write') && !in_array($a['status'], ['completed','cancelled'], true)): ?><a href="<?= e(url('index.php?page=appointment_edit&id=' . $a['id'])) ?>">Edit</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
