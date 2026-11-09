<?php
$u = require_login();
require_can('finance.view');
$hid = current_hospital_id();
$id = (int) get('id');
$invoice = scoped_or_404('invoices', $id);
$patient = row('SELECT * FROM patients WHERE id=? AND hospital_id=?', [$invoice['patient_id'], $hid]);
$lines = invoice_lines($invoice);
$payments = rows('SELECT * FROM invoice_payments WHERE invoice_id=? ORDER BY created_at DESC', [$id]);
$errors = [];

if (is_post()) {
    csrf_verify();
    require_can('finance.write');
    $action = post('action');
    if ($action === 'pay') {
        $amount = (int) post('amount');
        $method = post('method');
        $payResult = null;
        if (!in_array($method, ['cash', 'mtn_momo', 'airtel_money', 'bank', 'insurance'], true)) {
            $errors[] = 'Choose a valid payment method.';
        } else {
            // Re-read the invoice under a row lock so two cashiers can never overpay it or record on a cancelled/paid one.
            $payResult = transaction(function () use ($id, $hid, $u, $amount, $method) {
                $inv = row('SELECT * FROM invoices WHERE id=? AND hospital_id=? FOR UPDATE', [$id, $hid]);
                if (!$inv || !in_array($inv['status'], ['unpaid', 'partial'], true)) { return 'This invoice is ' . ($inv['status'] ?? 'not available') . ' and cannot take payments.'; }
                $remaining = (int) $inv['amount'] - (int) $inv['amount_paid'];
                if ($amount <= 0 || $amount > $remaining) { return 'Enter an amount between 1 and ' . money($remaining) . '.'; }
                insert('invoice_payments', ['invoice_id' => $id, 'amount' => $amount, 'method' => $method, 'reference' => post('reference') ?: null, 'recorded_by' => $u['id'], 'created_at' => now()]);
                $newPaid = (int) $inv['amount_paid'] + $amount;
                update('invoices', ['amount_paid' => $newPaid, 'status' => $newPaid >= (int) $inv['amount'] ? 'paid' : 'partial', 'updated_at' => now()], 'id=?', [$id]);
                return true;
            });
            if ($payResult === true) {
                audit('invoice.payment', 'invoice', $id, $invoice['patient_id'], ['amount' => $amount]);
                flash('success', 'Payment recorded.');
                redirect('index.php?page=invoice_view&id=' . $id);
            }
            $errors[] = (string) $payResult;
        }
    } elseif ($action === 'cancel') {
        if (in_array($invoice['status'], ['unpaid', 'partial'], true)) {
            update('invoices', ['status' => 'cancelled', 'updated_at' => now()], 'id=?', [$id]);
            audit('invoice.cancel', 'invoice', $id, $invoice['patient_id']);
            flash('info', 'Invoice cancelled.');
        } else {
            flash('error', 'Only unpaid or part-paid invoices can be cancelled.');
        }
        redirect('index.php?page=invoice_view&id=' . $id);
    }
}
$invoice = row('SELECT * FROM invoices WHERE id=?', [$id]);
render_page('Invoice ' . $invoice['invoice_no'], 'invoice_view', compact('invoice', 'patient', 'payments', 'errors', 'lines'));
