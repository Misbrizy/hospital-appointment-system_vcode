<div class="section-head"><h2>Active pregnancies <span class="muted">(<?= count($records) ?>)</span></h2></div>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Patient</th><th>LMP</th><th>Expected delivery</th><th>Risk</th><th>Next visit</th></tr></thead>
  <tbody>
  <?php if (!$records): ?><tr><td colspan="5" class="empty">No active pregnancy records.</td></tr><?php endif; ?>
  <?php foreach ($records as $r):
      $next = row('SELECT visit_no, planned_date FROM anc_visits WHERE pregnancy_id=? AND status IN ("planned","booked") ORDER BY planned_date LIMIT 1', [$r['id']]); ?>
    <tr>
      <td><a href="<?= e(url('index.php?page=anc_view&id=' . $r['id'])) ?>"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></a> <span class="muted mono"><?= e($r['patient_no']) ?></span></td>
      <td><?= e(fmt_date($r['lmp_date'])) ?></td>
      <td><?= e(fmt_date($r['edd_date'])) ?></td>
      <td><?= badge($r['risk_level']) ?></td>
      <td><?= $next ? 'Visit ' . $next['visit_no'] . ' · ' . e(fmt_date($next['planned_date'])) : '—' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
