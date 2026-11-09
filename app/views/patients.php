<div class="section-head">
  <h2>Patients <span class="muted">(<?= $p['total'] ?>)</span></h2>
  <?php if (can('patients.write')): ?><a class="btn" href="<?= e(url('index.php?page=patient_new')) ?>"><?= icon('plus') ?> New patient</a><?php endif; ?>
</div>
<form method="get" style="margin-bottom:14px;max-width:360px">
  <input type="hidden" name="page" value="patients">
  <input type="search" name="q" placeholder="Search by name, ID or phone" value="<?= e($search) ?>" onchange="this.form.submit()">
</form>
<div class="table-wrap">
<table class="list">
  <thead><tr><th>Patient</th><th>ID</th><th>Phone</th><th>Gender</th><th>Next appointment</th></tr></thead>
  <tbody>
  <?php if (!$patients): ?><tr><td colspan="5" class="empty">No patients found.</td></tr><?php endif; ?>
  <?php foreach ($patients as $pt):
      $next = row("SELECT appt_date, appt_time FROM appointments WHERE patient_id=? AND appt_date>=CURDATE() AND status IN ('scheduled','confirmed') ORDER BY appt_date, appt_time LIMIT 1", [$pt['id']]); ?>
    <tr>
      <td><a href="<?= e(url('index.php?page=patient_view&id=' . $pt['id'])) ?>" class="person"><span class="avatar"><?= e(initials(full_name($pt))) ?></span><?= e(full_name($pt)) ?></a></td>
      <td class="mono"><?= e($pt['patient_no']) ?></td>
      <td><?= e($pt['phone'] ?: '—') ?></td>
      <td><?= e(ucfirst($pt['gender'])) ?></td>
      <td><?= $next ? e(fmt_date($next['appt_date']) . ' · ' . fmt_time($next['appt_time'])) : '—' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?= pager($p) ?>
