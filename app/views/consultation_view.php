<style>@media print{.sidebar,.topbar,nav,.no-print,.btn{display:none!important}.card{box-shadow:none;border:0}body{background:#fff}}</style>
<div class="section-head no-print">
  <h2>Consultation <span class="mono muted"><?= e($rec['consultation_no'] ?: '#' . $rec['id']) ?></span></h2>
  <div style="display:flex;gap:8px">
    <button class="btn small ghost" type="button" onclick="window.print()">Print</button>
    <a class="btn small ghost" href="<?= e(url('index.php?page=patient_view&id=' . $patient['id'])) ?>">Patient record</a>
    <a class="btn small ghost" href="<?= e(url('index.php?page=consultations')) ?>">All consultations</a>
  </div>
</div>
<div class="card">
  <dl class="kv" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:6px 24px">
    <div><dt>Patient</dt><dd><strong><?= e(full_name($patient)) ?></strong> <span class="mono muted"><?= e($patient['patient_no']) ?></span></dd></div>
    <div><dt>Age / gender</dt><dd><?= $patient['dob'] ? (int) ((time() - strtotime($patient['dob'])) / 31557600) . ' yrs' : '—' ?> · <?= e(ucfirst($patient['gender'])) ?></dd></div>
    <div><dt>Doctor</dt><dd><?= e($doctor['name'] ?? '—') ?><?= !empty($doctor['specialty']) ? ' · ' . e($doctor['specialty']) : '' ?></dd></div>
    <div><dt>Department</dt><dd><?= e($dept['name'] ?? '—') ?></dd></div>
    <div><dt>Consultation date &amp; time</dt><dd><?= e(fmt_dt($rec['consulted_at'] ?? $rec['created_at'])) ?></dd></div>
    <?php if ($appt): ?><div><dt>Appointment</dt><dd><?= e($appt['appt_no']) ?> · <?= e($appt['appt_type']) ?></dd></div><?php endif; ?>
  </dl>
</div>

<div class="card" style="margin-top:14px">
  <?php if ($content['legacy'] !== null): ?>
    <h3>Clinical note <span class="muted" style="font-weight:400">(recorded before structured notes)</span></h3>
    <p style="white-space:pre-wrap"><?= e($content['legacy']) ?></p>
  <?php else: ?>
    <?php if ($content['vitals']): ?>
      <h3>Vital signs</h3>
      <p><?php foreach ($content['vitals'] as $k => $v): ?><span style="margin-right:18px"><strong><?= e(CONSULTATION_VITALS[$k][0] ?? $k) ?>:</strong> <?= e($v) ?> <?= e(CONSULTATION_VITALS[$k][1] ?? '') ?></span><?php endforeach; ?></p>
    <?php endif; ?>
    <?php foreach (CONSULTATION_SECTIONS as $k => [$label]): if (($content['sections'][$k] ?? '') === '') { continue; } ?>
      <h3 style="margin-bottom:4px"><?= e($label) ?></h3>
      <p style="white-space:pre-wrap;margin-top:0"><?= e($content['sections'][$k]) ?></p>
    <?php endforeach; ?>
  <?php endif; ?>
  <?php if ($rec['follow_up_date']): ?><h3 style="margin-bottom:4px">Follow-up date</h3><p style="margin-top:0"><?= e(fmt_date($rec['follow_up_date'])) ?></p><?php endif; ?>
</div>

<?php foreach ($addenda as $a): ?>
<div class="card" style="margin-top:14px;border-left:4px solid var(--brand, #0a7)">
  <h3 style="margin:0 0 4px">Addendum · <?= e(fmt_dt($a['consulted_at'] ?? $a['created_at'])) ?> <span class="muted" style="font-weight:400">· <?= e($a['doctor_name'] ?? '—') ?></span></h3>
  <p style="white-space:pre-wrap;margin:0"><?= e($a['text']) ?></p>
</div>
<?php endforeach; ?>

<?php if ((int) $rec['doctor_id'] === (int) current_user()['id'] && can('medical.write')): ?>
<div class="card no-print" style="margin-top:14px">
  <h3>Add an addendum</h3>
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <p class="hint">Use this to correct or add information. It is stored as a new dated entry; the original note above is never changed.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="addendum">
    <div class="field"><textarea name="addendum" rows="3" maxlength="5000" required></textarea></div>
    <button class="btn small" type="submit">Add addendum</button>
  </form>
</div>
<?php endif; ?>
