<?php
require dirname(__DIR__) . '/app/bootstrap.php';

if (!current_user()) {
    echo view('landing', ['demo_sent' => get('demo') === '1']);
    exit;
}

$page = get('page', 'dashboard');
$allowed = [
    'dashboard', 'patients', 'patient_new', 'patient_view', 'patient_edit',
    'appointments', 'appointment_new', 'appointment_edit', 'appointment_view',
    'queue', 'consultation', 'consultation_view', 'consultations', 'invoice_print', 'anc', 'anc_new', 'anc_view',
    'reschedule', 'sms_templates', 'sms_template_edit', 'reminder_rules', 'sms_logs',
    'reports', 'users', 'user_new', 'user_edit', 'departments', 'audit_logs', 'settings', 'billing',
    'notifications', 'account', 'twofa_setup',
    'portal', 'portal_history', 'portal_book',
    'hospitals', 'payments', 'plans', 'enterprise_requests',
    'finance_dashboard', 'invoices', 'invoice_new', 'invoice_view', 'expenses',
];
if (!in_array($page, $allowed, true)) {
    abort(404);
}
// Central role/permission guard (see config/pages.php) — runs before any page code.
$pageRule = (require APP_ROOT . '/config/pages.php')[$page] ?? null;
if ($pageRule) {
    $gate = require_login();
    if (isset($pageRule['roles']) && !in_array($gate['role'], $pageRule['roles'], true)) { abort(403); }
    if (isset($pageRule['perm']) && !can($pageRule['perm'])) { abort(403); }
}
$file = APP_ROOT . '/app/pages/' . $page . '.php';
if (!is_file($file)) {
    abort(404);
}
require $file;
