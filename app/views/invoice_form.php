<?php
$rowsPosted = [];
if (is_post()) { foreach ((array) ($_POST['item_desc'] ?? []) as $i => $d) { $rowsPosted[] = [(string) $d, (string) ($_POST['item_qty'][$i] ?? ''), (string) ($_POST['item_price'][$i] ?? '')]; } }
if (!$rowsPosted) { $rowsPosted = [['', '1', '']]; }
?>
<div class="card" style="max-width:820px">
<?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" id="invForm">
  <?= csrf_field() ?>
  <div class="row2">
    <div class="field">
      <label>Patient</label>
      <select name="patient_id" required>
        <option value="">— Select patient —</option>
        <?php foreach ($allPatients as $pt): ?>
          <option value="<?= $pt['id'] ?>" <?= selected((string) (is_post() ? post('patient_id') : ($preselectedPatient['id'] ?? '')), (string) $pt['id']) ?>><?= e($pt['first_name'] . ' ' . $pt['last_name']) ?> (<?= e($pt['patient_no']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label>Due date (optional)</label><input type="date" name="due_date" value="<?= pv('due_date') ?>"></div>
  </div>

  <label style="font-weight:600">Services &amp; treatments</label>
  <div class="table-wrap"><table class="list" id="items">
    <thead><tr><th>Description</th><th style="width:90px">Qty</th><th style="width:140px">Unit price</th><th style="width:140px;text-align:right">Line total</th><th style="width:40px"></th></tr></thead>
    <tbody>
    <?php foreach ($rowsPosted as [$d, $q, $pr]): ?>
      <tr>
        <td><input name="item_desc[]" maxlength="255" placeholder="e.g. Consultation fee, Lab test" value="<?= e($d) ?>"></td>
        <td><input name="item_qty[]" type="number" min="1" step="1" value="<?= e($q) ?>"></td>
        <td><input name="item_price[]" type="number" min="0" step="1" value="<?= e($pr) ?>"></td>
        <td class="lt" style="text-align:right">0</td>
        <td><button type="button" class="btn small ghost rm" aria-label="Remove line">×</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p><button type="button" class="btn small ghost" id="addRow">+ Add line</button></p>

  <div class="row2">
    <div class="field"><label>Tax rate (%)</label><input type="number" name="tax_rate" min="0" max="100" step="0.01" value="<?= pv('tax_rate', '0') ?>"></div>
    <div class="field"><label>Additional charges (RWF)</label><input type="number" name="other_charges" min="0" step="1" value="<?= pv('other_charges', '0') ?>"></div>
  </div>
  <div class="field"><label>Additional charges label (optional)</label><input name="other_charges_label" maxlength="80" placeholder="e.g. Service fee" value="<?= pv('other_charges_label') ?>"></div>

  <div class="card" style="background:var(--surface-2,#f6f8f9);margin:10px 0 16px">
    <dl class="kv"><dt>Subtotal</dt><dd id="tSub">0</dd><dt>Tax</dt><dd id="tTax">0</dd><dt>Additional charges</dt><dd id="tOth">0</dd><dt><strong>Total</strong></dt><dd><strong id="tTot">0</strong> RWF</dd></dl>
    <p class="hint" style="margin:0">Totals are recalculated by the server when you save.</p>
  </div>
  <button class="btn" type="submit">Create invoice</button>
  <a class="btn ghost" href="<?= e(url('index.php?page=invoices')) ?>">Cancel</a>
</form>
</div>
<script>
(function(){
  var f=document.getElementById('invForm'), tb=f.querySelector('#items tbody');
  var fmt=function(n){return Math.round(n).toLocaleString('en-US');};
  function calc(){
    var sub=0;
    tb.querySelectorAll('tr').forEach(function(r){
      var q=parseInt(r.querySelector('[name="item_qty[]"]').value)||0, p=parseInt(r.querySelector('[name="item_price[]"]').value)||0;
      r.querySelector('.lt').textContent=fmt(q*p); sub+=q*p;
    });
    var rate=parseFloat(f.tax_rate.value)||0, tax=Math.round(sub*rate/100), oth=parseInt(f.other_charges.value)||0;
    document.getElementById('tSub').textContent=fmt(sub); document.getElementById('tTax').textContent=fmt(tax);
    document.getElementById('tOth').textContent=fmt(oth); document.getElementById('tTot').textContent=fmt(sub+tax+oth);
  }
  f.addEventListener('input',calc);
  document.getElementById('addRow').onclick=function(){
    var r=tb.rows[0].cloneNode(true); r.querySelectorAll('input').forEach(function(i){i.value=i.name==='item_qty[]'?'1':'';}); tb.appendChild(r); calc();
  };
  tb.addEventListener('click',function(e){ if(e.target.classList.contains('rm') && tb.rows.length>1){ e.target.closest('tr').remove(); calc(); } });
  calc();
})();
</script>
