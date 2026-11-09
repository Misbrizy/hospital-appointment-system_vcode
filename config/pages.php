<?php
/**
 * Central page guard. The router (public/index.php) checks this table BEFORE a page file runs, so a page that
 * forgot its own check can never be reached by the wrong role. Each page still does its own checks as well
 * (defence in depth), and record-level rules (e.g. a doctor's own patients) live in app/access.php.
 *
 * 'perm' = the role must hold this permission (see config/permissions.php); 'roles' = only these roles.
 * Pages not listed here are open to any signed-in user (dashboard, notifications, account, 2FA).
 */
return [
    'patients' => ['perm' => 'patients.view'], 'patient_view' => ['perm' => 'patients.view'],
    'patient_new' => ['perm' => 'patients.write'], 'patient_edit' => ['perm' => 'patients.write'],
    'appointments' => ['perm' => 'appointments.view'],
    'appointment_new' => ['perm' => 'appointments.write'], 'appointment_edit' => ['perm' => 'appointments.write'],
    'queue' => ['perm' => 'queue.view'],
    'consultation' => ['perm' => 'medical.write'], 'consultation_view' => ['perm' => 'medical.view'], 'consultations' => ['perm' => 'medical.view'],
    'anc' => ['perm' => 'anc.view'], 'anc_view' => ['perm' => 'anc.view'], 'anc_new' => ['perm' => 'anc.write'],
    'reschedule' => ['perm' => 'reschedule.review'],
    'reports' => ['perm' => 'reports.view'],
    'sms_logs' => ['perm' => 'sms.view'],
    'audit_logs' => ['perm' => 'audit.view'],
    'finance_dashboard' => ['perm' => 'finance.view'], 'invoices' => ['perm' => 'finance.view'], 'invoice_view' => ['perm' => 'finance.view'],
    'invoice_print' => ['perm' => 'finance.view'], 'invoice_new' => ['perm' => 'finance.write'],
    'expenses' => ['perm' => 'finance.view'],
    'sms_templates' => ['roles' => ['hospital_admin']], 'sms_template_edit' => ['roles' => ['hospital_admin']], 'reminder_rules' => ['roles' => ['hospital_admin']],
    'users' => ['roles' => ['hospital_admin']], 'user_new' => ['roles' => ['hospital_admin']], 'user_edit' => ['roles' => ['hospital_admin']],
    'departments' => ['roles' => ['hospital_admin']], 'settings' => ['roles' => ['hospital_admin']], 'billing' => ['roles' => ['hospital_admin']],
    'portal' => ['roles' => ['patient']], 'portal_book' => ['roles' => ['patient']], 'portal_history' => ['roles' => ['patient']],
    'hospitals' => ['roles' => ['super_admin']], 'payments' => ['roles' => ['super_admin']], 'plans' => ['roles' => ['super_admin']], 'enterprise_requests' => ['roles' => ['super_admin']],
];
