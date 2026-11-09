<div class="section-head">
  <div class="person"><span class="avatar" style="width:46px;height:46px;font-size:1rem"><?= e(initials(full_name($patient))) ?></span>
    <div><h2 style="margin:0"><?= e(full_name($patient)) ?></h2><span class="muted mono"><?= e($patient['patient_no']) ?></span></div>
  </div>
  <div style="display:flex;gap:8px">
    <?php if (can('appointments.write') && $history): ?><a class="btn small" href="<?= e(url('index.php?page=appointment_new&patient_id=' . $patient['id'])) ?>"><?= icon('plus') ?> Book appointment</a><?php endif; ?>
    <?php if (can('finance.write')): ?><a class="btn small ghost" href="<?= e(url('index.php?page=invoice_new&patient_id=' . $patient['id'])) ?>"><?= icon('card') ?> New invoice</a><?php endif; ?>
    <?php if (can('reports.view') && $history): ?><a class="btn small ghost" href="<?= e(url('index.php?page=reports&patient_id=' . $patient['id'])) ?>"><?= icon('chart') ?> View report</a><?php endif; ?>
    <?php if (can('patients.write')): ?><a class="btn ghost small" href="<?= e(url('index.php?page=patient_edit&id=' . $patient['id'])) ?>">Edit</a><?php endif; ?>
  </div>
</div>

<div class="grid" style="grid-template-columns:2fr 1fr;align-items:start">
<div>
  <div class="tabs">
    <?php if ($history): ?><a class="active" href="#appointments">Appointments</a><?php endif; ?>
    <?php if ($invoices): ?><a href="#invoices">Invoices</a><?php endif; ?>
    <?php if (can('anc.view')): ?><a href="#anc">Pregnancy / ANC</a><?php endif; ?>
    <?php if (can('sms.view')): ?><a href="#sms">Message history</a><?php endif; ?>
    <?php if (can('medical.view')): ?><a href="#medical">Medical notes</a><?php endif; ?>
  </div>

  <?php if ($history): ?>
  <div class="card" style="margin-bottom:20px">
    <h3>Appointments</h3>
    <?php if (!$appointments): ?><p class="empty">No appointments yet.</p><?php else: ?>
    <div class="table-wrap"><table class="list">
      <thead><tr><th>Date</th><th>Type</th><th>Doctor</th><th>Status</th><th></th></tr></thead>
      <tbody><?php foreach ($appointments as $a): ?>
        <tr>
          <td><?= e(fmt_date($a['appt_date'])) ?> <span class="muted"><?= e(fmt_time($a['appt_time'])) ?></span></td>
          <td><?= e($a['appt_type']) ?><?= $a['dept_name'] ? ' · ' . e($a['dept_name']) : '' ?></td>
          <td><?= e($a['doctor_name'] ?? '—') ?></td>
          <td><?= badge($a['status']) ?></td>
          <td><?php if (can('appointments.write') && !in_array($a['status'], ['completed','cancelled'], true)): ?><a href="<?= e(url('index.php?page=appointment_edit&id=' . $a['id'])) ?>">Edit</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($invoices): ?>
  <div class="card" style="margin-bottom:20px" id="invoices">
    <h3>Invoices</h3>
    <div class="table-wrap"><table class="list">
      <thead><tr><th>No.</th><th>Date</th><th>Total</th><th>Paid</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($invoices as $inv): ?>
        <tr><td class="mono"><a href="<?= e(url('index.php?page=invoice_view&id=' . $inv['id'])) ?>"><?= e($inv['invoice_no']) ?></a></td><td><?= e(fmt_date($inv['created_at'])) ?></td><td><?= e(money($inv['amount'])) ?></td><td><?= e(money($inv['amount_paid'])) ?></td><td><?= badge($inv['status']) ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div>
  <?php endif; ?>

  <?php if (can('anc.view')): ?>
  <div class="card" style="margin-bottom:20px" id="anc">
    <div class="section-head" style="margin-bottom:10px"><h3 style="margin:0">Pregnancy / ANC</h3>
      <?php if (can('anc.write')): ?><a class="btn small ghost" href="<?= e(url('index.php?page=anc_new&patient_id=' . $patient['id'])) ?>">New pregnancy record</a><?php endif; ?>
    </div>
    <?php if (!$pregnancies): ?><p class="empty">No pregnancy records.</p><?php else: foreach ($pregnancies as $preg): ?>
      <p><a href="<?= e(url('index.php?page=anc_view&id=' . $preg['id'])) ?>">LMP <?= e(fmt_date($preg['lmp_date'])) ?> — EDD <?= e(fmt_date($preg['edd_date'])) ?></a> <?= badge($preg['risk_level']) ?> <?= badge($preg['status']) ?></p>
    <?php endforeach; endif; ?>
  </div>
  <?php endif; ?>

  <?php if (can('sms.view')): ?>
  <div class="card" id="sms">
    <h3>Recent messages</h3>
    <?php if (!$smsLogs): ?><p class="empty">No messages sent yet.</p><?php else: ?>
    <div class="table-wrap"><table class="list">
      <thead><tr><th>When</th><th>Message</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($smsLogs as $s): ?>
        <tr><td><?= e(fmt_dt($s['created_at'])) ?></td><td><?= e(mb_strimwidth($s['body'], 0, 70, '…')) ?></td><td><?= badge($s['status']) ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if (can('medical.view')): ?>
  <div class="card" style="margin-top:20px" id="medical">
    <div class="section-head" style="margin-bottom:10px"><h3 style="margin:0">Consultation history</h3><a class="btn small ghost" href="<?= e(url('index.php?page=consultations&q=' . urlencode($patient['patient_no']))) ?>">Open list</a></div>
    <?php if (!$medical): ?><p class="empty">No consultations recorded by you for this patient.</p><?php else: foreach ($medical as $m): ?>
      <div class="timeline"><div class="ev"><div class="t"><a href="<?= e(url('index.php?page=consultation_view&id=' . $m['id'])) ?>"><?= e(fmt_dt($m['consulted_at'] ?? $m['created_at'])) ?></a> · <?= e($m['doctor_name'] ?? '—') ?><?= !empty($m['dept_name']) ? ' · ' . e($m['dept_name']) : '' ?> <span class="muted mono"><?= e($m['consultation_no'] ?? '') ?></span></div>
        <p><?= e(consultation_preview($m['notes_enc'], 160)) ?></p><?php if ($m['follow_up_date']): ?><p class="muted">Follow-up: <?= e(fmt_date($m['follow_up_date'])) ?></p><?php endif; ?></div></div>
    <?php endforeach; endif; ?>
  </div>
  <?php endif; ?>
</div>

<div>
  <div class="card">
    <h3>Details</h3>
    <dl class="kv">
      <dt>Date of birth</dt><dd><?= $patient['dob'] ? e(fmt_date($patient['dob'])) : '—' ?></dd>
      <dt>Gender</dt><dd><?= e(ucfirst($patient['gender'])) ?></dd>
      <dt>Phone</dt><dd><?= e($patient['phone'] ?: '—') ?></dd>
      <dt>Address</dt><dd><?= e($patient['address'] ?: '—') ?></dd>
      <dt>Blood group</dt><dd><?= e($patient['blood_group'] ?: '—') ?></dd>
      <dt>Emergency</dt><dd><?= e($patient['emergency_name'] ?: '—') ?><?= $patient['emergency_phone'] ? ' · ' . e($patient['emergency_phone']) : '' ?></dd>
      <dt>Insurance</dt><dd><?= e($patient['insurance_provider'] ?: '—') ?><?= $patient['insurance_number'] ? ' · ' . e($patient['insurance_number']) : '' ?></dd>
      <dt>SMS reminders</dt><dd><?= $patient['sms_opt_in'] ? 'On' : 'Off' ?> (<?= e($patient['preferred_language'] === 'en' ? 'English' : 'Kinyarwanda') ?>)</dd>
    </dl>
  </div>
  <?php if (can('portal.manage')): ?>
  <div class="card" style="margin-top:16px">
    <h3>Patient portal</h3>
    <?php if ($patient['user_id']): ?>
      <p class="muted">This patient has a portal account and can view their own appointments.</p>
    <?php else: ?>
      <p class="muted">Give this patient their own sign-in to view appointments and request reschedules.</p>
      <form method="post" onsubmit="return confirm('Create a portal account for this patient?')">
        <?= csrf_field() ?><input type="hidden" name="action" value="create_portal">
        <?php if (!$patient['phone']): ?><div class="field"><label>Email (no phone on file)</label><input name="portal_email" type="email"></div><?php endif; ?>
        <button class="btn small" type="submit">Create portal account</button>
      </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
</div>
