<?php
$u = require_login();
require_can('finance.view');
$hid = current_hospital_id();
$thisMonth = date('Y-m-01');

$stats = [
    'revenue_month' => (int) val("SELECT COALESCE(SUM(amount_paid),0) FROM invoices WHERE hospital_id=? AND updated_at >= ?", [$hid, $thisMonth]),
    'outstanding' => (int) val("SELECT COALESCE(SUM(amount - amount_paid),0) FROM invoices WHERE hospital_id=? AND status IN ('unpaid','partial')", [$hid]),
    'unpaid_count' => (int) val("SELECT COUNT(*) FROM invoices WHERE hospital_id=? AND status IN ('unpaid','partial')", [$hid]),
    'expenses_month' => (int) val("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE hospital_id=? AND expense_date >= ?", [$hid, $thisMonth]),
    'invoices_month' => (int) val("SELECT COUNT(*) FROM invoices WHERE hospital_id=? AND created_at >= ?", [$hid, $thisMonth]),
];
$stats['net_month'] = $stats['revenue_month'] - $stats['expenses_month'];

$recentInvoices = rows("SELECT i.*, p.first_name, p.last_name FROM invoices i JOIN patients p ON p.id=i.patient_id WHERE i.hospital_id=? ORDER BY i.created_at DESC LIMIT 8", [$hid]);
render_page('Finance dashboard', 'finance_dashboard', compact('stats', 'recentInvoices'));
