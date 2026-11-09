<?php
$u = require_login();
require_can('finance.write');
$hid = current_hospital_id();
$errors = [];
$patientId = (int) get('patient_id');
$preselectedPatient = $patientId ? row('SELECT * FROM patients WHERE id=? AND hospital_id=? AND deleted_at IS NULL', [$patientId, $hid]) : null;
$allPatients = rows('SELECT id, first_name, last_name, patient_no FROM patients WHERE hospital_id=? AND deleted_at IS NULL ORDER BY first_name LIMIT 500', [$hid]);

if (is_post()) {
    csrf_verify();
    $pid = (int) post('patient_id');
    $patient = row('SELECT * FROM patients WHERE id=? AND hospital_id=? AND deleted_at IS NULL', [$pid, $hid]);
    $due = post('due_date');
    [$items, $errors] = invoice_items_from_post();
    $taxRaw = str_replace(',', '.', trim((string) post('tax_rate', '0')));
    $taxRate = is_numeric($taxRaw) ? (float) $taxRaw : -1;
    $otherRaw = trim((string) post('other_charges', '0'));
    $label = mb_substr(post('other_charges_label'), 0, 80);
    if (!$patient) { $errors[] = 'Choose a valid patient.'; }
    if ($taxRate < 0 || $taxRate > 100) { $errors[] = 'Tax rate must be between 0 and 100.'; $taxRate = 0; }
    if ($otherRaw !== '' && !ctype_digit($otherRaw)) { $errors[] = 'Additional charges must be a whole amount.'; $otherRaw = '0'; }
    if ($due !== '' && !valid_date($due)) { $errors[] = 'Enter a valid due date.'; }

    if (!$errors) {
        $t = invoice_totals($items, $taxRate, (int) $otherRaw);
        if ($t['total'] <= 0) { $errors[] = 'The invoice total must be greater than zero.'; }
    }
    if (!$errors) {
        $summary = mb_substr(implode('; ', array_column($items, 'description')), 0, 255);   // keeps the old one-line description meaningful in lists
        $invId = transaction(function () use ($hid, $u, $pid, $items, $t, $taxRate, $label, $due, $summary) {
            q('UPDATE hospitals SET invoice_seq=LAST_INSERT_ID(invoice_seq+1) WHERE id=?', [$hid]);
            $seq = (int) db()->query('SELECT LAST_INSERT_ID()')->fetchColumn();
            $no = 'INV-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            $id = insert('invoices', [
                'hospital_id' => $hid, 'patient_id' => $pid, 'invoice_no' => $no, 'description' => $summary,
                'subtotal' => $t['subtotal'], 'tax_rate' => $taxRate, 'tax_amount' => $t['tax_amount'],
                'other_charges' => $t['other_charges'], 'other_charges_label' => $label !== '' ? $label : null,
                'amount' => $t['total'], 'amount_paid' => 0, 'status' => 'unpaid', 'due_date' => $due ?: null,
                'created_by' => $u['id'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($items as $i => $it) {
                insert('invoice_items', ['invoice_id' => $id, 'line_no' => $i + 1] + $it);
            }
            return $id;
        });
        audit('invoice.create', 'invoice', $invId, $pid, ['amount' => $t['total'], 'lines' => count($items)]);
        flash('success', 'Invoice created.');
        redirect('index.php?page=invoice_view&id=' . $invId);
    }
}
render_page('New invoice', 'invoice_form', compact('errors', 'allPatients', 'preselectedPatient'));
