<?php
$u = require_login();
require_can('patients.view');
$hid = current_hospital_id();
$id = (int) get('id');
$patient = scoped_or_404('patients', $id);
require_patient_access($id);   // doctors: only patients assigned to them
audit('patient.view', 'patient', $id, $id);
$history = can('patients.history');
$ownOnly = $u['role'] === 'doctor' ? ' AND a.doctor_id=' . (int) $u['id'] : '';
$appointments = rows("SELECT a.*, d.name AS dept_name, doc.name AS doctor_name FROM appointments a
    LEFT JOIN departments d ON d.id=a.department_id LEFT JOIN users doc ON doc.id=a.doctor_id
    WHERE a.patient_id=? AND a.hospital_id=? $ownOnly ORDER BY a.appt_date DESC, a.appt_time DESC LIMIT 30", [$id, $hid]);
if (!$history) { $appointments = []; }
$pregnancies = can('anc.view') ? rows('SELECT * FROM pregnancy_records WHERE patient_id=? ORDER BY created_at DESC', [$id]) : [];
$smsLogs = can('sms.view') ? rows('SELECT * FROM sms_logs WHERE patient_id=? AND hospital_id=? ORDER BY created_at DESC LIMIT 15', [$id, $hid]) : [];
$invoices = can('finance.view') ? rows('SELECT id, invoice_no, amount, amount_paid, status, created_at FROM invoices WHERE patient_id=? AND hospital_id=? ORDER BY created_at DESC LIMIT 10', [$id, $hid]) : [];
// Consultations: only the ones this user may read (a doctor sees only the ones they wrote).
$medical = can('medical.view') ? rows('SELECT m.*, doc.name AS doctor_name, dep.name AS dept_name FROM medical_records m LEFT JOIN users doc ON doc.id=m.doctor_id LEFT JOIN departments dep ON dep.id=m.department_id
    WHERE m.patient_id=? AND m.hospital_id=? AND m.addendum_of IS NULL' . (can('medical.view_all') ? '' : ' AND m.doctor_id=' . (int) $u['id']) . ' ORDER BY COALESCE(m.consulted_at, m.created_at) DESC', [$id, $hid]) : [];

if (is_post() && post('action') === 'create_portal' && can('portal.manage')) {
    csrf_verify();
    if (!$patient['user_id'] && ($patient['phone'] || post('portal_email'))) {
        $email = post('portal_email') ?: null;
        if ($email && row('SELECT id FROM users WHERE email=?', [$email])) {
            flash('error', 'That email is already used by another account.');
        } elseif ($patient['phone'] && row('SELECT id FROM users WHERE phone=?', [$patient['phone']])) {
            flash('error', 'That phone number is already used by another account.');
        } else {
            $temp = random_password();
            $uid = insert('users', ['hospital_id' => $hid, 'role' => 'patient', 'name' => full_name($patient), 'email' => $email, 'phone' => $patient['phone'], 'password_hash' => hash_password($temp), 'must_change_password' => 1, 'is_active' => 1, 'created_at' => now()]);
            update('patients', ['user_id' => $uid], 'id=?', [$id]);
            audit('portal.create', 'patient', $id, $id);
            flash('success', "Portal account created. Temporary password: $temp");
        }
    }
    redirect('index.php?page=patient_view&id=' . $id);
}
render_page(full_name($patient), 'patient_view', compact('patient', 'appointments', 'pregnancies', 'smsLogs', 'medical', 'history', 'invoices'));
