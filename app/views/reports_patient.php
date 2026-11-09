<div class="section-head">
  <h2>Report — <?= e(full_name($patient)) ?> <span class="muted mono"><?= e($patient['patient_no']) ?></span></h2>
  <div style="display:flex;gap:8px" class="no-print">
    <button class="btn ghost small" type="button" onclick="window.print()">Print / Export PDF</button>
    <a class="btn small" href="<?= e(url('index.php?page=reports')) ?>">Back to reports</a>
  </div>
</div>

<form method="get" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;align-items:end" class="no-print">
  <input type="hidden" name="page" value="reports"><input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
  <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <button class="btn small" type="submit">Update</button>
</form>

<div class="grid stat-grid" style="margin-bottom:24px">
  <div class="stat"><span class="n"><?= (int) $apptStats['total'] ?></span><span class="l">Appointments</span></div>
  <div class="stat leaf"><span class="n"><?= (int) $apptStats['completed'] ?></span><span class="l">Completed</span></div>
  <div class="stat danger"><span class="n"><?= (int) $apptStats['missed'] ?></span><span class="l">Missed</span></div>
  <div class="stat amber"><span class="n"><?= (int) $apptStats['cancelled'] ?></span><span class="l">Cancelled</span></div>
  <div class="stat"><span class="n"><?= (int) $apptStats['upcoming'] ?></span><span class="l">Upcoming</span></div>
</div>

<div class="grid stat-grid" style="margin-bottom:24px">
  <div class="stat"><span class="n"><?= (int) $reminderStats['total'] ?></span><span class="l">Reminders sent</span></div>
  <div class="stat leaf"><span class="n"><?= (int) $reminderStats['delivered'] ?></span><span class="l">Delivered</span></div>
  <div class="stat danger"><span class="n"><?= (int) $reminderStats['failed'] ?></span><span class="l">Failed</span></div>
  <div class="stat"><span class="n"><?= (int) $reminderStats['sms_count'] ?> SMS · <?= (int) $reminderStats['email_count'] ?> email · <?= (int) $reminderStats['wa_count'] ?> WhatsApp</span><span class="l">By channel</span></div>
</div>

<?php if ($invoiceStats): ?>
<div class="grid stat-grid" style="margin-bottom:24px">
  <div class="stat"><span class="n"><?= (int) $invoiceStats['total'] ?></span><span class="l">Invoices</span></div>
  <div class="stat"><span class="n"><?= money($invoiceStats['billed']) ?></span><span class="l">Billed</span></div>
  <div class="stat leaf"><span class="n"><?= money($invoiceStats['paid']) ?></span><span class="l">Paid</span></div>
</div>
<?php endif; ?>

<h3>Appointment history</h3>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Date</th><th>Type</th><th>Department</th><th>Doctor</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$apptHistory): ?><tr><td colspan="5" class="empty">No appointments in this range.</td></tr><?php endif; ?>
  <?php foreach ($apptHistory as $a): ?>
    <tr><td><?= e(fmt_date($a['appt_date'])) ?></td><td><?= e($a['appt_type']) ?></td><td><?= e($a['dept_name'] ?? '—') ?></td><td><?= e($a['doctor_name'] ?? '—') ?></td><td><?= badge($a['status']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
