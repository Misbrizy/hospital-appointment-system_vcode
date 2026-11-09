<?php
$hid = current_hospital_id();
[$scopeSql, $scopeParams] = doctor_patient_scope(current_user(), 'id');   // doctors only see their own patients here
$allPatients = rows('SELECT id, first_name, last_name, patient_no, phone FROM patients WHERE hospital_id=? AND deleted_at IS NULL' . $scopeSql . ' ORDER BY first_name LIMIT 500', array_merge([$hid], $scopeParams));
$a = $appt ?? [];
$selectedRules = $a ? array_filter(explode(',', (string) $a['reminder_rule_ids'])) : array_column($rules, 'id');
$viewer = current_user();
$isDoctorViewer = $viewer['role'] === 'doctor';
?>
<div class="card" style="max-width:760px">
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" action="<?= e($action) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label>Patient</label>
    <select name="patient_id" required <?= $a ? 'disabled' : '' ?>>
      <option value="">— Select patient —</option>
      <?php foreach ($allPatients as $pt): $sel = $preselectedPatient['id'] ?? ($a['patient_id'] ?? null); ?>
        <option value="<?= $pt['id'] ?>" <?= selected((string) $sel, (string) $pt['id']) ?>><?= e(full_name($pt)) ?> (<?= e($pt['patient_no']) ?><?= $pt['phone'] ? ', ' . e($pt['phone']) : '' ?>)</option>
      <?php endforeach; ?>
    </select>
    <?php if ($a): ?><input type="hidden" name="patient_id" value="<?= (int) $a['patient_id'] ?>"><p class="hint">Patient can't be changed on an existing appointment — cancel and rebook if needed.</p><?php endif; ?>
    <?php if (!$a && current_user()['role'] === 'doctor'): ?><p class="hint">You can book follow-ups for your own patients. New patients are registered and first booked by reception.</p>
    <?php elseif (!$a): ?><p class="hint">Don't see them? <a href="<?= e(url('index.php?page=patient_new')) ?>">Register a new patient</a> first.</p><?php endif; ?>
  </div>
  <div class="row2">
    <?php if ($isDoctorViewer): ?>
      <div class="field"><label>Department</label>
        <div class="card" style="padding:10px 12px;background:var(--teal-wash);border:none;font-size:.87rem;color:var(--teal-deep)"><?= e($a ? ($apptDept['name'] ?? 'Not set') : ($departments[0]['name'] ?? 'Not set')) ?></div>
        <?php if (!$a): ?><input type="hidden" id="department_id" name="department_id" value="<?= (int) ($departments[0]['id'] ?? 0) ?>"><?php endif; ?>
      </div>
      <div class="field"><label>Doctor</label>
        <div class="card" style="padding:10px 12px;background:var(--teal-wash);border:none;font-size:.87rem;color:var(--teal-deep)"><?= e($viewer['name']) ?> (<?= $a ? 'you' : 'you — this will be your appointment' ?>)</div>
      </div>
    <?php else: ?>
    <div class="field"><label>Department</label><select id="department_id" name="department_id" required>
      <option value="">— Select department —</option>
      <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= selected($a['department_id'] ?? '', $d['id']) ?>><?= e($d['name']) ?></option><?php endforeach; ?>
    </select></div>
    <?php if ($a): ?>
    <div class="field"><label>Doctor</label><select name="doctor_id">
      <option value="">— Unassigned —</option>
      <?php foreach (($doctors ?? []) as $d): ?><option value="<?= $d['id'] ?>" <?= selected($a['doctor_id'] ?? '', $d['id']) ?>><?= e($d['name']) ?><?= !empty($d['dept_name']) ? ' · ' . e($d['dept_name']) : ($d['specialty'] ? ' · ' . e($d['specialty']) : '') ?></option><?php endforeach; ?>
    </select><p class="hint">Reassign the doctor here if needed.</p></div>
    <?php else: ?>
    <div class="field"><label>Doctor</label><div class="card" style="padding:10px 12px;background:var(--teal-wash);border:none;font-size:.87rem;color:var(--teal-deep)">A doctor in this department will be assigned automatically based on availability.</div></div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="row3">
    <div class="field"><label>Appointment type</label><input name="appt_type" required list="appt-types" value="<?= pv('appt_type', $a['appt_type'] ?? '') ?>"></div>
    <div class="field"><label>Date</label><input type="date" id="appt_date" name="appt_date" required min="<?= date('Y-m-d') ?>" value="<?= pv('appt_date', $a['appt_date'] ?? '') ?>"></div>
    <div class="field"><label>Time</label><input type="time" id="appt_time" name="appt_time" required value="<?= pv('appt_time', $a['appt_time'] ?? '') ?>"></div>
  </div>
  <?php if (!$a): ?>
  <div class="field">
    <label>Available times</label>
    <div id="slot-box" class="card" style="padding:10px 12px;background:var(--paper)"><p class="hint">Select a date to view the available time slots.</p></div>
  </div>
  <?php endif; ?>
  <datalist id="appt-types"><option>Consultation</option><option>Antenatal Care</option><option>Follow-up</option><option>Vaccination</option><option>Lab test</option></datalist>
  <div class="field"><label>Notes (scheduling only — no clinical detail)</label><textarea name="notes" rows="2"><?= pv('notes', $a['notes'] ?? '') ?></textarea></div>
  <?php if (!$a): ?>
  <div class="field">
    <label>SMS reminders to send</label>
    <?php foreach ($rules as $r): ?>
      <label class="checkline" style="margin-bottom:6px"><input type="checkbox" name="rules[]" value="<?= $r['id'] ?>" <?= in_array((string) $r['id'], (array) $selectedRules, true) ? 'checked' : '' ?>> <?= e($r['label']) ?></label><br>
    <?php endforeach; ?>
    <?php if (!$rules): ?><p class="hint">No reminder rules configured yet. <a href="<?= e(url('index.php?page=reminder_rules')) ?>">Set them up</a>.</p><?php endif; ?>
  </div>
  <?php endif; ?>
  <button class="btn" type="submit">Save appointment</button>
  <a class="btn ghost" href="<?= e(url('index.php?page=appointments')) ?>">Back</a>
</form>
</div>
<?php if (!$a): ?>
<?= view('_slot_picker', ['deptFieldId' => 'department_id', 'dateFieldId' => 'appt_date', 'timeFieldId' => 'appt_time', 'boxId' => 'slot-box']) ?>
<?php endif; ?>
<?php if ($a && !in_array($a['status'], ['completed', 'cancelled'], true)): ?>
<div class="card" style="max-width:760px;margin-top:16px">
  <h3 style="margin-top:0">Cancel this appointment</h3>
  <form method="post" action="<?= e($action) ?>" onsubmit="return confirm('Cancel this appointment and notify the patient by SMS?')">
    <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
    <div class="field"><label>Reason (optional)</label><input name="cancel_reason"></div>
    <button class="btn danger" type="submit">Cancel appointment</button>
  </form>
</div>
<?php endif; ?>
