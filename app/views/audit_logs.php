<div class="section-head"><h2>Audit log</h2></div>
<p class="muted">Every sign-in and record change, with who did it and when.</p>
<div class="table-wrap"><table class="list">
  <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead>
  <tbody>
  <?php if (!$logs): ?><tr><td colspan="5" class="empty">Nothing logged yet.</td></tr><?php endif; ?>
  <?php foreach ($logs as $l): ?>
    <tr>
      <td><?= e(fmt_dt($l['created_at'])) ?></td>
      <td><?= e($l['user_name'] ?? 'System') ?></td>
      <td class="mono"><?= e($l['action']) ?></td>
      <td><?= e($l['entity'] ? $l['entity'] . ' #' . $l['entity_id'] : '—') ?></td>
      <td class="mono"><?= e($l['ip']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?= pager($p) ?>
