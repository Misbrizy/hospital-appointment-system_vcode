<?php
$u = require_role('super_admin');
if (is_post()) {
    csrf_verify();
    $id = (int) post('id');
    $pay = row('SELECT * FROM payments WHERE id=? AND status=\'pending\'', [$id]);
    if ($pay) {
        $decision = post('decision');
        if ($decision === 'confirm') {
            $plan = row('SELECT * FROM plans WHERE id=?', [$pay['plan_id']]);
            $months = $pay['period'] === 'yearly' ? 12 : 1;
            transaction(function () use ($pay, $plan, $months) {
                $h = row('SELECT * FROM hospitals WHERE id=?', [$pay['hospital_id']]);
                $base = max($h['subscription_ends_at'] ?? date('Y-m-d'), date('Y-m-d'));
                update('hospitals', ['plan_id' => $plan['id'], 'is_trial' => 0, 'status' => 'active', 'subscription_ends_at' => date('Y-m-d', strtotime($base . " +$months months"))], 'id=?', [$pay['hospital_id']]);
                update('payments', ['status' => 'confirmed', 'confirmed_by' => null, 'confirmed_at' => now(), 'period_start' => date('Y-m-d'), 'period_end' => date('Y-m-d', strtotime($base . " +$months months"))], 'id=?', [$pay['id']]);
            });
            audit('payment.confirm', 'payment', $id);
            flash('success', 'Payment confirmed and plan activated.');
        } else {
            update('payments', ['status' => 'rejected'], 'id=?', [$id]);
            audit('payment.reject', 'payment', $id);
            flash('info', 'Payment rejected.');
        }
    }
    redirect('index.php?page=payments');
}
$pending = rows('SELECT p.*, h.name AS hospital_name, pl.name AS plan_name FROM payments p JOIN hospitals h ON h.id=p.hospital_id JOIN plans pl ON pl.id=p.plan_id WHERE p.status=\'pending\' ORDER BY p.created_at');
render_page('Payments', 'payments', compact('pending'));
