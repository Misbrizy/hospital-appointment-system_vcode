<?php
// Print-ready invoice (A4). Same authorization as viewing the invoice: finance.view + the invoice must belong to this hospital.
$u = require_login();
require_can('finance.view');
$hid = current_hospital_id();
$id = (int) get('id');
$invoice = scoped_or_404('invoices', $id);
$patient = row('SELECT * FROM patients WHERE id=? AND hospital_id=?', [$invoice['patient_id'], $hid]);
if (!$patient) { abort(404); }
$hospital = row('SELECT * FROM hospitals WHERE id=?', [$hid]);
$lines = invoice_lines($invoice);
$payments = rows('SELECT * FROM invoice_payments WHERE invoice_id=? ORDER BY created_at', [$id]);
$issuedBy = $invoice['created_by'] ? (string) val('SELECT name FROM users WHERE id=?', [$invoice['created_by']]) : '';
audit('invoice.print', 'invoice', $id, (int) $invoice['patient_id']);
echo view('invoice_print', compact('invoice', 'patient', 'hospital', 'lines', 'payments', 'issuedBy', 'u'));
