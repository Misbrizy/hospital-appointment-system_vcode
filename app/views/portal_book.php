<div class="section-head"><h2><?= e(t('portal.book_title')) ?></h2></div>
<div class="card" style="max-width:520px">
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="field"><label><?= e(t('portal.department')) ?></label><select id="department_id" name="department_id" required>
    <option value=""><?= e(t('portal.choose')) ?></option>
    <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= selected(pv('department_id'), $d['id']) ?>><?= e($d['name']) ?></option><?php endforeach; ?>
  </select></div>
  <div class="field"><label><?= e(t('portal.what_treat')) ?></label><input name="appt_type" required list="appt-types" value="<?= pv('appt_type') ?>" placeholder="Consultation"></div>
  <datalist id="appt-types"><option>Consultation</option><option>Antenatal Care</option><option>Follow-up</option><option>Vaccination</option><option>Lab test</option></datalist>
  <div class="row2">
    <div class="field"><label><?= e(t('portal.date')) ?></label><input type="date" id="appt_date" name="appt_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= pv('appt_date') ?>"></div>
    <div class="field"><label><?= e(t('portal.time')) ?></label><input type="time" id="appt_time" name="appt_time" required value="<?= pv('appt_time') ?>"></div>
  </div>
  <div class="field">
    <label><?= e(t('portal.available_times')) ?></label>
    <div id="slot-box" class="card" style="padding:10px 12px;background:var(--paper)"><p class="hint"><?= e(t('portal.choose')) ?></p></div>
  </div>
  <p class="hint"><?= e(t('portal.no_doctor_pick')) ?></p>
  <button class="btn" type="submit"><?= e(t('portal.send_request')) ?></button>
  <a class="btn ghost" href="<?= e(url('index.php?page=portal')) ?>"><?= e(t('common.cancel')) ?></a>
</form>
</div>
<?= view('_slot_picker', ['deptFieldId' => 'department_id', 'dateFieldId' => 'appt_date', 'timeFieldId' => 'appt_time', 'boxId' => 'slot-box']) ?>
