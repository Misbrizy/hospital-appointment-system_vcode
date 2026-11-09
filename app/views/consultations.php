<div class="section-head"><h2>Consultations <span class="muted">(<?= (int) $p['total'] ?>)</span></h2></div>
<form method="get" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:14px">
  <input type="hidden" name="page" value="consultations">
  <div class="field" style="margin:0"><label>Search</label><input name="q" value="<?= e($q) ?>" placeholder="Patient name, ID or CN-…"></div>
  <div class="field" style="margin:0"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="field" style="margin:0"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <button class="btn small" type="submit">Filter</button>
</form>
<div class="table-wrap"><table class="list">
  <thead><tr><th>No.</th><th>Date</th><th>Patient</th><th>Department</th><th>Diagnosis / summary</th><th>Follow-up</th></tr></thead>
  <tbody>
  <?php if (!$records): ?><tr><td colspan="6" class="empty">No consultations found.</td></tr><?php endif; ?>
  <?php foreach ($records as $r): ?>
    <tr>
      <td class="mono"><a href="<?= e(url('index.php?page=consultation_view&id=' . $r['id'])) ?>"><?= e($r['consultation_no'] ?: '#' . $r['id']) ?></a></td>
      <td><?= e(fmt_dt($r['consulted_at'] ?? $r['created_at'])) ?></td>
      <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?> <span class="muted mono"><?= e($r['patient_no']) ?></span></td>
      <td><?= e($r['dept_name'] ?? '—') ?></td>
      <td><?= e(consultation_preview($r['notes_enc'])) ?><?= (int) $r['addenda'] ? ' <span class="muted">(+' . (int) $r['addenda'] . ' addendum)</span>' : '' ?></td>
      <td><?= $r['follow_up_date'] ? e(fmt_date($r['follow_up_date'])) : '—' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?= pager($p) ?>
