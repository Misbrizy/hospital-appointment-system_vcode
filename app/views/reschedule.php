<div class="section-head"><h2>Reschedule requests <span class="muted">(<?= count($pending) ?>)</span></h2></div>
<?php if (!$pending): ?><div class="card empty">No pending requests.</div><?php endif; ?>
<?php foreach ($pending as $r): ?>
<div class="card" style="margin-bottom:14px">
  <div class="section-head" style="margin-bottom:8px">
    <div><strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong> — <?= e($r['appt_type']) ?>
      <div class="muted">Currently: <?= e(fmt_date($r['current_date'])) ?> · <?= e(fmt_time($r['current_time'])) ?></div>
      <div>Requested: <strong><?= e(fmt_date($r['requested_date'])) ?><?= $r['requested_time'] ? ' · ' . e(fmt_time($r['requested_time'])) : '' ?></strong></div>
      <?php if ($r['reason']): ?><div class="muted">Reason: <?= e($r['reason']) ?></div><?php endif; ?>
    </div>
  </div>
  <form method="post" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
    <div class="field" style="margin:0"><label>Confirm time</label><input type="time" name="confirm_time" value="<?= e($r['requested_time'] ?: $r['current_time']) ?>"></div>
    <div class="field" style="margin:0;flex:1;min-width:180px"><label>Note (optional)</label><input name="note"></div>
    <button class="btn" name="decision" value="approve" type="submit">Approve</button>
    <button class="btn danger" name="decision" value="reject" type="submit">Reject</button>
  </form>
</div>
<?php endforeach; ?>
