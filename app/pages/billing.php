<?php
$u = require_role('hospital_admin');
$hid = current_hospital_id();
$hospital = row('SELECT h.*, pl.name AS plan_name, pl.code AS plan_code, pl.price_month, pl.price_year, pl.sort_order AS plan_sort_order FROM hospitals h JOIN plans pl ON pl.id=h.plan_id WHERE h.id=?', [$hid]);
$plans = rows('SELECT * FROM plans WHERE is_active=1 ORDER BY sort_order');
$usage = plan_usage($hid);
$payments = rows('SELECT * FROM payments WHERE hospital_id=? ORDER BY created_at DESC LIMIT 15', [$hid]);
$enterpriseRequests = rows('SELECT * FROM enterprise_requests WHERE hospital_id=? ORDER BY created_at DESC LIMIT 5', [$hid]);
$errors = [];

if (is_post()) {
    csrf_verify();
    $form = post('form');

    if ($form === 'enterprise') {
        $msg = post('message');
        if ($msg === '') { $errors[] = 'Please describe what your hospital needs.'; }
        if (!$errors) {
            insert('enterprise_requests', ['hospital_id' => $hid, 'requested_by' => $u['id'], 'message' => $msg, 'phone' => normalize_phone(post('phone')), 'status' => 'new', 'created_at' => now()]);
            $superAdmins = rows("SELECT id FROM users WHERE role='super_admin' AND is_active=1");
            foreach ($superAdmins as $sa) {
                notify((int) $sa['id'], 'Enterprise request: ' . $hospital['name'], mb_substr($msg, 0, 120));
            }
            audit('enterprise.request', 'hospital', $hid);
            flash('success', 'Your request was sent. Our team will contact you about an Enterprise plan.');
            redirect('index.php?page=billing');
        }
    } else {
        $planId = (int) post('plan_id');
        $plan = row('SELECT * FROM plans WHERE id=? AND is_active=1', [$planId]);
        $period = post('period') === 'yearly' ? 'yearly' : 'monthly';
        if (!$plan || $plan['code'] === 'enterprise') { $errors[] = 'Choose a valid plan.'; }
        $method = post('method');
        if (!in_array($method, ['mtn_momo', 'airtel_money', 'bank', 'cash'], true)) { $errors[] = 'Choose a payment method.'; }
        if (!$errors) {
            $amount = $period === 'yearly' ? $plan['price_year'] : $plan['price_month'];
            insert('payments', [
                'hospital_id' => $hid, 'plan_id' => $planId, 'period' => $period, 'amount' => $amount, 'currency' => 'RWF',
                'method' => $method, 'reference' => post('reference') ?: null, 'status' => 'pending', 'submitted_by' => $u['id'], 'created_at' => now(),
            ]);
            audit('payment.submit', 'payment', null, null, ['plan' => $plan['code'], 'period' => $period]);
            flash('success', 'Payment submitted for confirmation. Your plan will update once it\'s confirmed.');
            redirect('index.php?page=billing');
        }
    }
}
render_page('Billing & plan', 'billing', compact('hospital', 'plans', 'usage', 'payments', 'enterpriseRequests', 'errors'));
