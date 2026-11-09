<?php $pt = $patient ?? []; ?>
<div class="card" style="max-width:760px">
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" action="<?= e($action) ?>">
  <?= csrf_field() ?>
  <div class="row2">
    <div class="field"><label>First name</label><input name="first_name" required value="<?= pv('first_name', $pt['first_name'] ?? '') ?>"></div>
    <div class="field"><label>Last name</label><input name="last_name" required value="<?= pv('last_name', $pt['last_name'] ?? '') ?>"></div>
  </div>
  <div class="row3">
    <div class="field"><label>Date of birth</label><input type="date" name="dob" value="<?= pv('dob', $pt['dob'] ?? '') ?>"></div>
    <div class="field"><label>Gender</label><select name="gender">
      <?php foreach (['female'=>'Female','male'=>'Male','other'=>'Other'] as $k=>$l): ?><option value="<?= $k ?>" <?= selected($pt['gender'] ?? 'female', $k) ?>><?= $l ?></option><?php endforeach; ?>
    </select></div>
    <div class="field"><label>Blood group</label><input name="blood_group" placeholder="e.g. O+" value="<?= pv('blood_group', $pt['blood_group'] ?? '') ?>"></div>
  </div>
  <div class="row2">
    <div class="field"><label>Phone</label><input name="phone" type="tel" placeholder="07XXXXXXXX" value="<?= pv('phone', $pt['phone'] ?? '') ?>"></div>
    <div class="field"><label>Preferred reminder language</label><select name="preferred_language">
      <option value="rw" <?= selected($pt['preferred_language'] ?? 'rw', 'rw') ?>>Kinyarwanda</option>
      <option value="en" <?= selected($pt['preferred_language'] ?? 'rw', 'en') ?>>English</option>
    </select></div>
  </div>
  <div class="row2">
    <div class="field"><label>Email (optional)</label><input name="email" type="email" value="<?= pv('email', $pt['email'] ?? '') ?>"></div>
    <div class="field"><label>WhatsApp number (if different from phone)</label><input name="whatsapp_phone" type="tel" value="<?= pv('whatsapp_phone', $pt['whatsapp_phone'] ?? '') ?>"></div>
  </div>
  <div class="field"><label>Address</label><input name="address" value="<?= pv('address', $pt['address'] ?? '') ?>"></div>
  <div class="row2">
    <div class="field"><label>Emergency contact name</label><input name="emergency_name" value="<?= pv('emergency_name', $pt['emergency_name'] ?? '') ?>"></div>
    <div class="field"><label>Emergency contact phone</label><input name="emergency_phone" type="tel" value="<?= pv('emergency_phone', $pt['emergency_phone'] ?? '') ?>"></div>
  </div>
  <div class="row2">
    <div class="field"><label>Insurance provider</label><input name="insurance_provider" value="<?= pv('insurance_provider', $pt['insurance_provider'] ?? '') ?>"></div>
    <div class="field"><label>Insurance number</label><input name="insurance_number" value="<?= pv('insurance_number', $pt['insurance_number'] ?? '') ?>"></div>
  </div>
  <div class="field" style="margin-bottom:18px">
    <label>Send reminders by</label>
    <label class="checkline"><input type="checkbox" name="sms_opt_in" value="1" <?= checked(($pt['sms_opt_in'] ?? 1) == 1) ?>> SMS</label>
    <label class="checkline"><input type="checkbox" name="email_opt_in" value="1" <?= checked(($pt['email_opt_in'] ?? 0) == 1) ?>> Email</label>
    <label class="checkline"><input type="checkbox" name="whatsapp_opt_in" value="1" <?= checked(($pt['whatsapp_opt_in'] ?? 0) == 1) ?>> WhatsApp</label>
  </div>
  <button class="btn" type="submit">Save patient</button>
  <a class="btn ghost" href="<?= e(url('index.php?page=patients')) ?>">Cancel</a>
</form>
</div>
