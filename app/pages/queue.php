<?php
$u = require_login();
require_can('queue.view');
$hid = current_hospital_id();
$today = date('Y-m-d');

if (is_post()) {
    csrf_verify();
    $id = (int) post('appointment_id');
    $appt = scoped_or_404('appointments', $id, $hid);
    if ($u['role'] === 'doctor') { require_appointment_access($appt); }   // a doctor can only progress their own patients
    if (!in_array($appt['status'], ['scheduled', 'confirmed', 'checked_in'], true)) { flash('error', 'That appointment can no longer be moved in the queue.'); redirect('index.php?page=queue'); }
    $action = post('action');
    $map = ['checkin' => 'checked_in', 'start' => 'in_consultation'];
    $allowed = isset($map[$action]) && ($action === 'start' ? can('medical.write') : can('queue.checkin'));
    if ($allowed) {
        $data = ['status' => $map[$action], 'updated_at' => now()];
        if ($action === 'checkin') {
            $data['checked_in_at'] = now();
            $data['queue_no'] = (int) val("SELECT COALESCE(MAX(queue_no),0)+1 FROM appointments WHERE hospital_id=? AND appt_date=?", [$hid, $appt['appt_date']]);
        } elseif ($action === 'start') {
            $data['consult_started_at'] = now();
        }
        update('appointments', $data, 'id=?', [$id]);
        audit('queue.' . $action, 'appointment', $id, $appt['patient_id']);
        if ($action === 'start') {
            redirect('index.php?page=consultation&id=' . $id);
        }
    }
    redirect('index.php?page=queue');
}

$doctorFilter = $u['role'] === 'doctor' ? ' AND a.doctor_id=' . (int) $u['id'] : '';
$waiting = rows("SELECT a.*, p.first_name, p.last_name, doc.name AS doctor_name FROM appointments a JOIN patients p ON p.id=a.patient_id LEFT JOIN users doc ON doc.id=a.doctor_id WHERE a.hospital_id=? AND a.appt_date=? AND a.status='checked_in' $doctorFilter ORDER BY a.queue_no", [$hid, $today]);
$inConsult = rows("SELECT a.*, p.first_name, p.last_name, doc.name AS doctor_name FROM appointments a JOIN patients p ON p.id=a.patient_id LEFT JOIN users doc ON doc.id=a.doctor_id WHERE a.hospital_id=? AND a.appt_date=? AND a.status='in_consultation' $doctorFilter ORDER BY a.consult_started_at", [$hid, $today]);
$upcoming = rows("SELECT a.*, p.first_name, p.last_name, doc.name AS doctor_name FROM appointments a JOIN patients p ON p.id=a.patient_id LEFT JOIN users doc ON doc.id=a.doctor_id WHERE a.hospital_id=? AND a.appt_date=? AND a.status IN ('scheduled','confirmed') $doctorFilter ORDER BY a.appt_time", [$hid, $today]);
render_page('Queue', 'queue', compact('waiting', 'inConsult', 'upcoming', 'today'));
