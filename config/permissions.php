<?php
/**
 * Role-based access control. Each role maps to the permissions it holds.
 * Clinical data (medical notes, pregnancy records) is deliberately limited to clinical roles:
 * neither the hospital administrator nor reception can open it.
 *
 * Notes on specific keys:
 *  - patients.view     demographics/contact (finance needs this to invoice); patients.history adds the clinical-operational
 *                      history on the patient page (appointments, message log) — finance does not hold it.
 *  - Doctors additionally only reach THEIR OWN patients/appointments/consultations: that record-level rule lives in
 *    app/access.php and is enforced on the server for every page, form post and direct URL.
 *  - medical.view_all  (not granted to any role by default) would let a role read every doctor's consultations.
 *  - reports.view      operational reports; finance uses its own dashboard, so it does not hold this.
 */
return [
    'super_admin' => ['hospitals.manage', 'plans.manage', 'payments.confirm', 'audit.view', 'notifications.view'],

    'hospital_admin' => [
        'users.manage', 'settings.manage', 'billing.view', 'audit.view', 'departments.manage',
        'templates.manage', 'reports.view', 'sms.view', 'reschedule.review',
        'patients.view', 'patients.history', 'patients.write', 'patients.archive', 'portal.manage', 'sms.manage',
        'appointments.view', 'appointments.write', 'appointments.cancel',
        'queue.view', 'queue.checkin', 'notifications.view', 'anc.view',
        'finance.view', 'finance.write',
    ],

    'doctor' => [
        'patients.view', 'patients.history', 'appointments.view', 'appointments.write', 'appointments.cancel',
        'queue.view', 'queue.progress', 'medical.view', 'medical.write',
        'anc.view', 'anc.write', 'reports.view', 'notifications.view', 'reschedule.review',
    ],

    'receptionist' => [
        'patients.view', 'patients.history', 'patients.write', 'portal.manage',
        'appointments.view', 'appointments.write', 'appointments.cancel',
        'queue.view', 'queue.checkin', 'reschedule.review', 'sms.view', 'notifications.view',
    ],

    'nurse' => [
        'patients.view', 'patients.history', 'patients.write', 'appointments.view',
        'queue.view', 'queue.checkin', 'queue.progress',
        'anc.view', 'anc.write', 'notifications.view',
    ],

    'patient' => ['portal.use', 'notifications.view'],

    'finance' => [
        'finance.view', 'finance.write', 'patients.view', 'notifications.view',
    ],
];
