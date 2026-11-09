<div class="section-head"><h2>Today's queue</h2><span class="muted"><?= e(fmt_date($today, 'l, d M Y')) ?></span></div>
<div class="grid" style="grid-template-columns:1fr 1fr 1fr;align-items:start">
  <div class="card">
    <h3>Upcoming <span class="muted">(<?= count($upcoming) ?>)</span></h3>
    <?php foreach ($upcoming as $a): ?>
      <div style="padding:9px 0;border-bottom:1px solid var(--line-soft)">
        <div class="person"><span class="avatar"><?= e(initials($a['first_name'] . ' ' . $a['last_name'])) ?></span>
          <div><strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong><div class="sub"><?= e(fmt_time($a['appt_time'])) ?> · <?= e($a['appt_type']) ?></div></div>
        </div>
        <?php if (can('queue.checkin')): ?>
        <form method="post" style="margin-top:6px"><?= csrf_field() ?><input type="hidden" name="appointment_id" value="<?= $a['id'] ?>"><input type="hidden" name="action" value="checkin">
          <button class="btn small block" type="submit">Check in</button></form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$upcoming): ?><p class="empty">Nothing upcoming.</p><?php endif; ?>
  </div>
  <div class="card">
    <h3>Waiting <span class="muted">(<?= count($waiting) ?>)</span></h3>
    <?php foreach ($waiting as $a): ?>
      <div style="padding:9px 0;border-bottom:1px solid var(--line-soft)">
        <strong>#<?= str_pad((string) $a['queue_no'], 2, '0', STR_PAD_LEFT) ?> <?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong>
        <div class="sub"><?= e($a['doctor_name'] ?? 'Unassigned') ?> · waiting since <?= e(fmt_time(date('H:i:s', strtotime($a['checked_in_at'])))) ?></div>
        <?php if (can('medical.write')): ?>
        <form method="post" style="margin-top:6px"><?= csrf_field() ?><input type="hidden" name="appointment_id" value="<?= $a['id'] ?>"><input type="hidden" name="action" value="start">
          <button class="btn small block ghost" type="submit">Start consultation</button></form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$waiting): ?><p class="empty">No one waiting.</p><?php endif; ?>
  </div>
  <div class="card">
    <h3>With doctor <span class="muted">(<?= count($inConsult) ?>)</span></h3>
    <?php foreach ($inConsult as $a): ?>
      <div style="padding:9px 0;border-bottom:1px solid var(--line-soft)">
        <strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong>
        <div class="sub"><?= e($a['doctor_name'] ?? '—') ?> · since <?= e(fmt_time(date('H:i:s', strtotime($a['consult_started_at'])))) ?></div>
        <?php if (can('medical.write')): ?>
          <a class="btn small block" href="<?= e(url('index.php?page=consultation&id=' . $a['id'])) ?>">Continue consultation</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$inConsult): ?><p class="empty">No one with a doctor.</p><?php endif; ?>
  </div>
</div>
