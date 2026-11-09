<div class="card" style="max-width:600px">
<p class="muted">For <strong><?= e(full_name($patient)) ?></strong></p>
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="field"><label>Last menstrual period (LMP) date</label><input type="date" name="lmp_date" required value="<?= pv('lmp_date') ?>"></div>
  <div class="row2">
    <div class="field"><label>Previous pregnancies</label><input type="number" min="0" name="previous_pregnancies" value="<?= pv('previous_pregnancies', '0') ?>"></div>
    <div class="field"><label>Risk level</label><select name="risk_level">
      <option value="low">Low</option><option value="moderate">Moderate</option><option value="high">High</option>
    </select></div>
  </div>
  <p class="hint">The expected delivery date and a standard 8-visit ANC schedule will be generated automatically. A clinician can adjust visit dates afterwards.</p>
  <button class="btn" type="submit">Create record</button>
  <a class="btn ghost" href="<?= e(url('index.php?page=patient_view&id=' . $patient['id'])) ?>">Cancel</a>
</form>
</div>
