<div class="section-head">
  <h2>Consultation — <?= e(full_name($patient)) ?></h2>
  <span class="muted"><?= e($appt['appt_type']) ?> · <?= $dept ? e($dept['name']) . ' · ' : '' ?><?= e(fmt_date($appt['appt_date'])) ?> <?= e(fmt_time($appt['appt_time'])) ?></span>
</div>
<div class="grid" style="grid-template-columns:2fr 1fr;align-items:start">
<div>
  <div class="card">
    <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
    <p class="hint" style="margin-top:0">Saved notes are encrypted, permanent and visible only to you. Corrections are added later as dated addenda — the original is never overwritten.</p>
    <form method="post">
      <?= csrf_field() ?>
      <?php $S = CONSULTATION_SECTIONS; ?>
      <?php foreach (['complaint', 'history'] as $k): ?>
      <div class="field"><label><?= e($S[$k][0]) ?><?= $k === 'complaint' ? ' *' : '' ?></label><textarea name="<?= $k ?>" rows="<?= $S[$k][1] ?>" maxlength="5000" placeholder="<?= e($S[$k][2]) ?>"><?= pv($k) ?></textarea></div>
      <?php endforeach; ?>

      <label style="font-weight:600">Vital signs <span class="muted" style="font-weight:400">(optional)</span></label>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin:6px 0 16px">
        <?php foreach (CONSULTATION_VITALS as $k => [$label, $unit]): ?>
          <div class="field" style="margin:0"><label style="font-size:.8rem"><?= e($label) ?> <span class="muted">(<?= e($unit) ?>)</span></label><input name="v_<?= $k ?>" value="<?= pv('v_' . $k) ?>" inputmode="decimal" autocomplete="off"></div>
        <?php endforeach; ?>
      </div>

      <?php foreach (['examination', 'diagnosis', 'treatment', 'medication', 'notes', 'followup_instructions'] as $k): ?>
      <div class="field"><label><?= e($S[$k][0]) ?></label><textarea name="<?= $k ?>" rows="<?= $S[$k][1] ?>" maxlength="5000" placeholder="<?= e($S[$k][2]) ?>"><?= pv($k) ?></textarea></div>
      <?php endforeach; ?>
      <div class="field"><label>Follow-up date (optional)</label><input type="date" name="follow_up_date" min="<?= date('Y-m-d') ?>" value="<?= pv('follow_up_date') ?>"></div>
      <button class="btn" type="submit" onclick="return confirm('Save this consultation? It cannot be edited afterwards (you can add an addendum).')">Save consultation &amp; complete visit</button>
      <a class="btn ghost" href="<?= e(url('index.php?page=queue')) ?>">Back to queue</a>
    </form>
  </div>
</div>
<div>
  <div class="card">
    <h3>Patient</h3>
    <dl class="kv">
      <dt>Patient ID</dt><dd class="mono"><?= e($patient['patient_no']) ?></dd>
      <dt>Age</dt><dd><?= $patient['dob'] ? (int) ((time() - strtotime($patient['dob'])) / 31557600) . ' yrs' : '—' ?></dd>
      <dt>Gender</dt><dd><?= e(ucfirst($patient['gender'])) ?></dd>
      <dt>Blood group</dt><dd><?= e($patient['blood_group'] ?: '—') ?></dd>
      <dt>Phone</dt><dd><?= e($patient['phone'] ?: '—') ?></dd>
    </dl>
  </div>
  <?php if ($history): ?>
  <div class="card" style="margin-top:14px">
    <h3>Your previous consultations</h3>
    <div class="timeline">
      <?php foreach ($history as $h): ?>
        <div class="ev"><div class="t"><a href="<?= e(url('index.php?page=consultation_view&id=' . $h['id'])) ?>"><?= e(fmt_date($h['consulted_at'] ?? $h['created_at'])) ?></a></div>
        <p style="font-size:.85rem"><?= e(consultation_preview($h['notes_enc'], 140)) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
</div>
