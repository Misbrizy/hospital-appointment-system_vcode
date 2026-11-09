<div class="section-head"><h2>Appointment history</h2></div>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Date</th><th>Type</th><th>Department</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$history): ?><tr><td colspan="4" class="empty">No appointments yet.</td></tr><?php endif; ?>
  <?php foreach ($history as $a): ?><tr><td><?= e(fmt_date($a['appt_date'])) ?></td><td><?= e($a['appt_type']) ?></td><td><?= e($a['dept_name'] ?? '—') ?></td><td><?= badge($a['status']) ?></td></tr><?php endforeach; ?>
  </tbody>
</table></div>
<?= pager($p) ?>
