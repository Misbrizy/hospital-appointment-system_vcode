<?php
$u = require_login();
require_can('reports.view');
$hid = current_hospital_id();
$from = get('from', date('Y-m-01'));
$to = get('to', date('Y-m-d'));
if (!valid_date($from)) { $from = date('Y-m-01'); }
if (!valid_date($to)) { $to = date('Y-m-d'); }

$isDoctor = $u['role'] === 'doctor';
$docAnd = $isDoctor ? ' AND a.doctor_id=' . (int) $u['id'] : '';       // a doctor's reports cover only their own appointments
$docAndPlain = $isDoctor ? ' AND doctor_id=' . (int) $u['id'] : '';
$patientId = (int) get('patient_id');
if ($patientId) {
    $patient = row('SELECT * FROM patients WHERE id=? AND hospital_id=?', [$patientId, $hid]);
    if ($patient) {
        require_patient_access($patientId);
        audit('report.patient', 'patient', $patientId, $patientId);
        $apptStats = row("SELECT COUNT(*) total, SUM(status='completed') completed, SUM(status='cancelled') cancelled, SUM(status='missed') missed, SUM(status IN ('scheduled','confirmed')) upcoming FROM appointments WHERE patient_id=? AND appt_date BETWEEN ? AND ?$docAndPlain", [$patientId, $from, $to]);
        $apptHistory = rows("SELECT a.*, d.name AS dept_name, doc.name AS doctor_name FROM appointments a LEFT JOIN departments d ON d.id=a.department_id LEFT JOIN users doc ON doc.id=a.doctor_id WHERE a.patient_id=? AND a.appt_date BETWEEN ? AND ?$docAnd ORDER BY a.appt_date DESC", [$patientId, $from, $to]);
        $reminderStats = !can('sms.view') ? ['total' => 0, 'delivered' => 0, 'sent' => 0, 'failed' => 0, 'sms_count' => 0, 'email_count' => 0, 'wa_count' => 0] : row("SELECT COUNT(*) total, SUM(status='delivered') delivered, SUM(status='sent') sent, SUM(status='failed') failed, SUM(channel='sms') sms_count, SUM(channel='email') email_count, SUM(channel='whatsapp') wa_count FROM sms_logs WHERE patient_id=? AND created_at BETWEEN ? AND ?", [$patientId, $from . ' 00:00:00', $to . ' 23:59:59']);
        $invoiceStats = can('finance.view') ? row("SELECT COUNT(*) total, COALESCE(SUM(amount),0) billed, COALESCE(SUM(amount_paid),0) paid FROM invoices WHERE patient_id=? AND created_at BETWEEN ? AND ?", [$patientId, $from . ' 00:00:00', $to . ' 23:59:59']) : null;
        render_page('Patient report — ' . full_name($patient), 'reports_patient', compact('patient', 'apptStats', 'apptHistory', 'reminderStats', 'invoiceStats', 'from', 'to'));
        exit;
    }
    flash('error', 'Patient not found.');
    redirect('index.php?page=reports');
}

[$scopeSql, $scopeParams] = doctor_patient_scope($u, 'id');
$allPatients = rows('SELECT id, first_name, last_name, patient_no FROM patients WHERE hospital_id=? AND deleted_at IS NULL' . $scopeSql . ' ORDER BY first_name LIMIT 1000', array_merge([$hid], $scopeParams));

$byStatus = row("SELECT COUNT(*) total, SUM(status='completed') completed, SUM(status='cancelled') cancelled, SUM(status='missed') missed, SUM(status IN ('scheduled','confirmed')) upcoming FROM appointments WHERE hospital_id=? AND appt_date BETWEEN ? AND ?$docAndPlain", [$hid, $from, $to]);
$sms = !can('sms.view') ? ['total' => 0, 'delivered' => 0, 'sent' => 0, 'failed' => 0] : row("SELECT COUNT(*) total, SUM(status='delivered') delivered, SUM(status='sent') sent, SUM(status='failed') failed FROM sms_logs WHERE hospital_id=? AND created_at BETWEEN ? AND ?", [$hid, $from . ' 00:00:00', $to . ' 23:59:59']);
$monthly = rows("SELECT DATE_FORMAT(appt_date,'%Y-%m') ym, COUNT(*) n FROM appointments WHERE hospital_id=? AND appt_date >= DATE_SUB(?, INTERVAL 5 MONTH)$docAndPlain GROUP BY ym ORDER BY ym", [$hid, $to]);
$byDept = rows("SELECT d.name, COUNT(*) n FROM appointments a JOIN departments d ON d.id=a.department_id WHERE a.hospital_id=? AND a.appt_date BETWEEN ? AND ?$docAnd GROUP BY d.name ORDER BY n DESC", [$hid, $from, $to]);
$byDoctor = rows("SELECT u.name, COUNT(*) n, SUM(a.status='completed') completed, SUM(a.status='missed') missed FROM appointments a JOIN users u ON u.id=a.doctor_id WHERE a.hospital_id=? AND a.appt_date BETWEEN ? AND ?$docAnd GROUP BY u.name ORDER BY n DESC", [$hid, $from, $to]);
render_page('Reports', 'reports', compact('byStatus', 'sms', 'monthly', 'byDept', 'byDoctor', 'from', 'to', 'allPatients'));
