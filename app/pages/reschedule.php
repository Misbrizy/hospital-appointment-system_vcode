<?php
$u = require_login();
require_can('reschedule.review');
$hid = current_hospital_id();
$isDoctor = $u['role'] === 'doctor';

if (is_post()) {
    csrf_verify();
    $id = (int) post('id');
    $req = row('SELECT r.* FROM reschedule_requests r JOIN appointments a ON a.id=r.appointment_id WHERE r.id=? AND r.hospital_id=?' . ($isDoctor ? ' AND a.doctor_id=?' : ''), $isDoctor ? [$id, $hid, $u['id']] : [$id, $hid]);
    if ($req && $req['status'] === 'pending') {
        $decision = post('decision');
        if ($decision === 'approve') {
            $appt = row('SELECT * FROM appointments WHERE id=?', [$req['appointment_id']]);
            $patient = row('SELECT * FROM patients WHERE id=?', [$req['patient_id']]);
            $newTime = post('confirm_time') ?: ($req['requested_time'] ?: $appt['appt_time']);
            update('appointments', ['appt_date' => $req['requested_date'], 'appt_time' => $newTime, 'status' => 'confirmed', 'updated_at' => now()], 'id=?', [$appt['id']]);
            cancel_pending_reminders($appt['id']);
            $fresh = row('SELECT * FROM appointments WHERE id=?', [$appt['id']]);
            queue_appointment_reminders($fresh, $patient, $hid, array_filter(array_map('intval', explode(',', (string) $appt['reminder_rule_ids']))) ?: null);
            send_appointment_sms('reschedule_approved', $fresh, $patient, $hid);
            if ($appt['doctor_id'] && (int) $appt['doctor_id'] !== (int) $u['id']) {
                notify((int) $appt['doctor_id'], 'Appointment moved: ' . full_name($patient), fmt_date($fresh['appt_date']) . ' at ' . fmt_time($fresh['appt_time']), url('index.php?page=appointment_edit&id=' . $appt['id']), $hid, (int) $appt['id']);
            }
            update('reschedule_requests', ['status' => 'approved', 'reviewed_by' => $u['id'], 'reviewed_at' => now(), 'response_note' => post('note') ?: null], 'id=?', [$id]);
            if ($patient['user_id']) {
                notify((int) $patient['user_id'], 'Your reschedule request was approved', fmt_date($fresh['appt_date']) . ' at ' . fmt_time($fresh['appt_time']), url('index.php?page=portal'), $hid);
            }
            audit('reschedule.approve', 'reschedule_request', $id, $req['patient_id']);
            flash('success', 'Reschedule approved and patient notified.');
        } else {
            update('reschedule_requests', ['status' => 'rejected', 'reviewed_by' => $u['id'], 'reviewed_at' => now(), 'response_note' => post('note') ?: null], 'id=?', [$id]);
            $patient = row('SELECT user_id FROM patients WHERE id=?', [$req['patient_id']]);
            if ($patient['user_id']) {
                notify((int) $patient['user_id'], 'Your reschedule request was declined', post('note') ?: 'Please contact the hospital to arrange another time.', url('index.php?page=portal'), $hid);
            }
            audit('reschedule.reject', 'reschedule_request', $id, $req['patient_id']);
            flash('info', 'Request rejected.');
        }
    }
    redirect('index.php?page=reschedule');
}

$where = "r.hospital_id=? AND r.status='pending'";
$params = [$hid];
if ($isDoctor) { $where .= ' AND a.doctor_id=?'; $params[] = $u['id']; }
$pending = rows("SELECT r.*, p.first_name, p.last_name, a.appt_date AS curr_appt_date, a.appt_time AS curr_appt_time, a.appt_type FROM reschedule_requests r JOIN patients p ON p.id=r.patient_id JOIN appointments a ON a.id=r.appointment_id WHERE $where ORDER BY r.created_at", $params);
render_page('Reschedule requests', 'reschedule', compact('pending'));
