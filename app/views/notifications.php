<div class="section-head"><h2>Notifications</h2></div>
<?php if (!$items): ?><div class="card empty">Nothing yet.</div><?php endif; ?>
<?php foreach ($items as $n): $isNew = in_array($n['id'], $wasUnread, true); ?>
  <div class="card" style="margin-bottom:10px;<?= $isNew ? 'border-left:4px solid var(--clay)' : '' ?>">
    <strong><?= e($n['title']) ?></strong>
    <?php if ($n['appt_no']): ?>
      <p class="muted" style="margin:4px 0 0"><?= e(fmt_date($n['appt_date'])) ?> at <?= e(fmt_time($n['appt_time'])) ?> · <strong>Department: <?= e($n['dept_name'] ?? '—') ?></strong><?= $n['doctor_name'] ? ' · ' . e($n['doctor_name']) : '' ?> · <?= e($n['appt_no']) ?> (<?= e(t('status.' . $n['appt_status']) !== 'status.' . $n['appt_status'] ? t('status.' . $n['appt_status']) : $n['appt_status']) ?>)</p>
    <?php elseif ($n['body']): ?><p class="muted" style="margin:4px 0 0"><?= e($n['body']) ?></p><?php endif; ?>
    <div class="muted" style="font-size:.78rem;margin-top:6px"><?= e(fmt_dt($n['created_at'])) ?><?php if ($n['link']): ?> · <a href="<?= e($n['link']) ?>">Open</a><?php endif; ?></div>
  </div>
<?php endforeach; ?>
