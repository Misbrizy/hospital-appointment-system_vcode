<div class="section-head">
  <h2><?= e(full_name($patient)) ?> — pregnancy record</h2>
  <a class="btn ghost small" href="<?= e(url('index.php?page=patient_view&id=' . $patient['id'])) ?>">Back to patient</a>
</div>
<div class="grid" style="grid-template-columns:1fr 260px;align-items:start">
<div class="card">
  <h3>ANC visit schedule</h3>
  <div class="table-wrap"><table class="list">
    <thead><tr><th>Visit</th><th>Week</th><th>Planned date</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($visits as $v): ?>
      <tr>
        <td>Visit <?= (int) $v['visit_no'] ?></td>
        <td>Week <?= (int) $v['planned_week'] ?></td>
        <td><?= e(fmt_date($v['planned_date'])) ?></td>
        <td><?= badge($v['status']) ?></td>
        <td><?php if ($v['status'] === 'planned' && can('anc.write')): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="book_visit"><input type="hidden" name="visit_id" value="<?= $v['id'] ?>"><button class="btn small" type="submit">Book</button></form>
        <?php elseif ($v['appointment_id']): ?><a href="<?= e(url('index.php?page=appointment_edit&id=' . $v['appointment_id'])) ?>">View appointment</a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<div class="card">
  <dl class="kv">
    <dt>LMP</dt><dd><?= e(fmt_date($preg['lmp_date'])) ?></dd>
    <dt>EDD</dt><dd><?= e(fmt_date($preg['edd_date'])) ?></dd>
    <dt>Previous</dt><dd><?= (int) $preg['previous_pregnancies'] ?></dd>
    <dt>Status</dt><dd><?= badge($preg['status']) ?></dd>
  </dl>
  <?php if (can('anc.write')): ?>
  <form method="post" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="action" value="set_risk">
    <div class="field"><label>Risk level</label><select name="risk_level" onchange="this.form.submit()">
      <?php foreach (['low','moderate','high'] as $lvl): ?><option value="<?= $lvl ?>" <?= selected($preg['risk_level'], $lvl) ?>><?= ucfirst($lvl) ?></option><?php endforeach; ?>
    </select></div>
  </form>
  <p class="hint">Risk level reflects a clinician's assessment; the system does not calculate it.</p>
  <?php endif; ?>
</div>
</div>
