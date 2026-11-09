<?php
$u = require_role('super_admin');
if (is_post()) {
    csrf_verify();
    $id = (int) post('id');
    update('plans', ['price_month' => (int) post('price_month'), 'price_year' => (int) post('price_year'), 'max_patients' => post('max_patients') === '' ? null : (int) post('max_patients'), 'max_sms_month' => post('max_sms_month') === '' ? null : (int) post('max_sms_month')], 'id=?', [$id]);
    audit('plan.update', 'plan', $id);
    flash('success', 'Plan updated.');
    redirect('index.php?page=plans');
}
$plans = rows('SELECT * FROM plans ORDER BY sort_order');
render_page('Plans', 'plans', compact('plans'));
