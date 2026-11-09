<?php $s = $staffUser ?? []; ?>
<div class="card" style="max-width:560px">
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="field"><label>Full name</label><input name="name" required value="<?= pv('name', $s['name'] ?? '') ?>"></div>
  <div class="row2">
    <div class="field"><label>Role</label><select name="role" <?= $s ? 'disabled' : '' ?> required>
      <?php foreach (['hospital_admin'=>'Hospital admin','doctor'=>'Doctor','nurse'=>'Nurse','receptionist'=>'Receptionist','finance'=>'Finance'] as $k=>$l): ?>
        <option value="<?= $k ?>" <?= selected($s['role'] ?? 'receptionist', $k) ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select><?php if ($s): ?><input type="hidden" name="role" value="<?= e($s['role']) ?>"><?php endif; ?></div>
    <div class="field"><label>Department</label><select name="department_id">
      <option value="">—</option>
      <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= selected($s['department_id'] ?? '', $d['id']) ?>><?= e($d['name']) ?></option><?php endforeach; ?>
    </select></div>
  </div>
  <div class="row2">
    <div class="field"><label>Email</label><input name="email" type="email" value="<?= pv('email', $s['email'] ?? '') ?>"></div>
    <div class="field"><label>Phone</label><input name="phone" type="tel" value="<?= pv('phone', $s['phone'] ?? '') ?>"></div>
  </div>
  <div class="field"><label>Specialty (doctors)</label><input name="specialty" value="<?= pv('specialty', $s['specialty'] ?? '') ?>"></div>
  <?php if (!$s): ?><p class="hint">A temporary password will be generated for you to share with them.</p><?php endif; ?>
  <button class="btn" type="submit">Save</button>
  <a class="btn ghost" href="<?= e(url('index.php?page=users')) ?>">Cancel</a>
</form>
<?php if ($s): ?>
<form method="post" action="<?= e(url('index.php?page=user_edit&id=' . $s['id'])) ?>" style="margin-top:16px;border-top:1px solid var(--line-soft);padding-top:16px">
  <?= csrf_field() ?><input type="hidden" name="toggle_active" value="1">
  <button class="btn <?= $s['is_active'] ? 'danger' : '' ?>" type="submit"><?= $s['is_active'] ? 'Disable account' : 'Re-enable account' ?></button>
  <button class="btn ghost" name="reset_password" value="1" type="submit" onclick="return confirm('Generate a new temporary password?')">Reset password</button>
</form>
<?php endif; ?>
</div>
