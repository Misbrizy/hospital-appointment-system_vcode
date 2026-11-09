<?php
$u = require_login();
if ($u['role'] === 'patient') { redirect('index.php?page=portal'); }
if ($u['role'] === 'super_admin') {
    $stats = [
        'hospitals' => (int) val("SELECT COUNT(*) FROM hospitals WHERE status='active'"),
        'trials' => (int) val("SELECT COUNT(*) FROM hospitals WHERE is_trial=1 AND status='active'"),
        'suspended' => (int) val("SELECT COUNT(*) FROM hospitals WHERE status='suspended'"),
        'patients' => (int) val('SELECT COUNT(*) FROM patients WHERE deleted_at IS NULL'),
        'appointments_today' => (int) val("SELECT COUNT(*) FROM appointments WHERE appt_date=CURDATE() AND status NOT IN ('cancelled')"),
        'sms_today' => (int) val("SELECT COUNT(*) FROM sms_logs WHERE DATE(created_at)=CURDATE() AND status<>'cancelled'"),
        'payments_pending' => (int) val("SELECT COUNT(*) FROM payments WHERE status='pending'"),
        'doctors_pending' => (int) val("SELECT COUNT(*) FROM users WHERE role='doctor' AND is_active=0"),
    ];
    $recentHospitals = rows('SELECT h.*, p.name AS plan_name FROM hospitals h JOIN plans p ON p.id=h.plan_id ORDER BY h.created_at DESC LIMIT 8');
    render_page('Dashboard', 'dashboard_super', compact('stats', 'recentHospitals'));
    exit;
}
$hid = current_hospital_id();
$today = date('Y-m-d');

$isDoctor = $u['role'] === 'doctor';
$dAnd = $isDoctor ? ' AND doctor_id=' . (int) $u['id'] : '';     // doctors see figures for their own patients only
[$scopeSql, $scopeParams] = doctor_patient_scope($u, 'id');
$stats = [
    'patients' => (int) val('SELECT COUNT(*) FROM patients WHERE hospital_id=? AND deleted_at IS NULL' . $scopeSql, array_merge([$hid], $scopeParams)),
    'today' => (int) val("SELECT COUNT(*) FROM appointments WHERE hospital_id=? AND appt_date=? AND status NOT IN ('cancelled')$dAnd", [$hid, $today]),
    'upcoming' => (int) val("SELECT COUNT(*) FROM appointments WHERE hospital_id=? AND appt_date>? AND status IN ('scheduled','confirmed')$dAnd", [$hid, $today]),
    'missed' => (int) val("SELECT COUNT(*) FROM appointments WHERE hospital_id=? AND status='missed' AND appt_date>=?$dAnd", [$hid, date('Y-m-d', strtotime('-30 days'))]),
    'sms_today' => can('sms.view') ? (int) val("SELECT COUNT(*) FROM sms_logs WHERE hospital_id=? AND DATE(created_at)=? AND status<>'cancelled'", [$hid, $today]) : 0,
    'sms_pending' => can('sms.view') ? (int) val("SELECT COUNT(*) FROM sms_logs WHERE hospital_id=? AND status='pending'", [$hid]) : 0,
    'waiting' => (int) val("SELECT COUNT(*) FROM appointments WHERE hospital_id=? AND appt_date=? AND status='checked_in'$dAnd", [$hid, $today]),
    'reschedule_pending' => (int) val("SELECT COUNT(*) FROM reschedule_requests WHERE hospital_id=? AND status='pending'", [$hid]),
];

$doctorFilter = $u['role'] === 'doctor' ? ' AND a.doctor_id = ' . (int) $u['id'] : '';
$schedule = rows("SELECT a.*, p.first_name, p.last_name, u.name AS doctor_name, d.name AS dept_name
    FROM appointments a JOIN patients p ON p.id=a.patient_id
    LEFT JOIN users u ON u.id=a.doctor_id LEFT JOIN departments d ON d.id=a.department_id
    WHERE a.hospital_id=? AND a.appt_date=? AND a.status NOT IN ('cancelled') $doctorFilter
    ORDER BY a.appt_time LIMIT 12", [$hid, $today]);

$usage = plan_usage($hid);
$schedulerStatus = can('sms.manage') ? scheduler_status() : null;
render_page('Dashboard', 'dashboard', compact('stats', 'schedule', 'today', 'usage', 'u', 'schedulerStatus'));
