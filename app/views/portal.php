<div class="section-head"><h2><?= e(t('portal.hello_name', ['name' => $patient['first_name']])) ?></h2></div>
<?php if (!$upcoming): ?><div class="card empty"><?= e(t('portal.no_upcoming')) ?></div><?php endif; ?>
<?php foreach ($upcoming as $a): ?>
<div class="card" style="margin-bottom:14px">
  <div class="section-head" style="margin-bottom:8px">
    <div><strong><?= e($a['appt_type']) ?></strong><?= $a['dept_name'] ? ' · ' . e($a['dept_name']) : '' ?>
      <div class="muted"><?= e(fmt_date($a['appt_date'], 'l, d M Y')) ?> <?= e(t('portal.at')) ?> <?= e(fmt_time($a['appt_time'])) ?><?= $a['doctor_name'] ? ' · ' . e($a['doctor_name']) : '' ?></div>
    </div>
    <?= badge($a['status']) ?>
  </div>
  <details>
    <summary style="cursor:pointer;color:var(--teal);font-size:.9rem"><?= e(t('portal.request_diff_date')) ?></summary>
    <form method="post" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
      <?= csrf_field() ?><input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
      <div class="field" style="margin:0"><label><?= e(t('portal.preferred_date')) ?></label><input type="date" name="requested_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required></div>
      <div class="field" style="margin:0"><label><?= e(t('portal.preferred_time')) ?></label><input type="time" name="requested_time"></div>
      <div class="field" style="margin:0;flex:1;min-width:180px"><label><?= e(t('portal.reason')) ?></label><input name="reason"></div>
      <button class="btn small" type="submit"><?= e(t('portal.send_request')) ?></button>
    </form>
  </details>
</div>
<?php endforeach; ?>
