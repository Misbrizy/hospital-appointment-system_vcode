<div class="section-head"><h2>Enterprise requests <span class="muted">(<?= count($requests) ?>)</span></h2></div>
<?php if (!$requests): ?><div class="card empty">No requests yet.</div><?php endif; ?>
<?php foreach ($requests as $r): ?>
<div class="card" style="margin-bottom:14px">
  <div class="section-head" style="margin-bottom:8px">
    <div><strong><?= e($r['hospital_name']) ?></strong><div class="muted"><?= e(fmt_dt($r['created_at'])) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></div></div>
    <?= badge($r['status']) ?>
  </div>
  <p><?= e($r['message']) ?></p>
  <form method="post" style="display:flex;gap:8px;align-items:center">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
    <select name="status" onchange="this.form.submit()">
      <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= selected($r['status'], $k) ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>
<?php endforeach; ?>
