<div class="section-head"><h2>Plans</h2></div>
<?php foreach ($plans as $p): ?>
<div class="card" style="max-width:520px;margin-bottom:14px">
  <h3><?= e($p['name']) ?> <span class="muted mono">(<?= e($p['code']) ?>)</span></h3>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
    <div class="row2">
      <div class="field"><label>Monthly price (RWF)</label><input type="number" name="price_month" value="<?= $p['price_month'] ?>"></div>
      <div class="field"><label>Yearly price (RWF)</label><input type="number" name="price_year" value="<?= $p['price_year'] ?>"></div>
    </div>
    <div class="row2">
      <div class="field"><label>Max patients (blank = unlimited)</label><input type="number" name="max_patients" value="<?= $p['max_patients'] ?>"></div>
      <div class="field"><label>Max SMS/month (blank = unlimited)</label><input type="number" name="max_sms_month" value="<?= $p['max_sms_month'] ?>"></div>
    </div>
    <button class="btn small" type="submit">Save</button>
  </form>
</div>
<?php endforeach; ?>
