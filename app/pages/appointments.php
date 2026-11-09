<?php
$u = require_login();
require_can('appointments.view');
$hid = current_hospital_id();
$date = get('date', date('Y-m-d'));
if (!valid_date($date)) { $date = date('Y-m-d'); }
$status = get('status');
$where = 'a.hospital_id=? AND a.appt_date=?';
$params = [$hid, $date];
if ($u['role'] === 'doctor') { $where .= ' AND a.doctor_id=?'; $params[] = $u['id']; }
if ($status !== '') { $where .= ' AND a.status=?'; $params[] = $status; }
$appts = rows("SELECT a.*, p.first_name, p.last_name, p.phone, doc.name AS doctor_name, d.name AS dept_name,
        rr.requested_date AS pending_requested_date, rr.requested_time AS pending_requested_time
    FROM appointments a JOIN patients p ON p.id=a.patient_id
    LEFT JOIN users doc ON doc.id=a.doctor_id LEFT JOIN departments d ON d.id=a.department_id
    LEFT JOIN reschedule_requests rr ON rr.appointment_id=a.id AND rr.status='pending'
    WHERE $where ORDER BY a.appt_time", $params);
render_page('Appointments', 'appointments', compact('appts', 'date', 'status'));
