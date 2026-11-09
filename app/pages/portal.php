<?php
$u = require_role('patient');
$patient = row('SELECT * FROM patients WHERE user_id=?', [$u['id']]);
if (!$patient) { abort(404, 'No patient record is linked to this account yet.'); }
$upcoming = rows("SELECT a.*, d.name AS dept_name, doc.name AS doctor_name FROM appointments a LEFT JOIN departments d ON d.id=a.department_id LEFT JOIN users doc ON doc.id=a.doctor_id WHERE a.patient_id=? AND a.appt_date>=CURDATE() AND a.status NOT IN ('cancelled','completed') ORDER BY a.appt_date, a.appt_time", [$patient['id']]);

if (is_post()) {
    csrf_verify();
    $apptId = (int) post('appointment_id');
    $appt = row('SELECT * FROM appointments WHERE id=? AND patient_id=?', [$apptId, $patient['id']]);
    $date = post('requested_date');
    if ($appt && valid_date($date) && $date > date('Y-m-d')) {
        insert('reschedule_requests', ['hospital_id' => $appt['hospital_id'], 'appointment_id' => $apptId, 'patient_id' => $patient['id'], 'requested_date' => $date, 'requested_time' => post('requested_time') ?: null, 'reason' => post('reason') ?: null, 'status' => 'pending', 'created_at' => now()]);
        audit('reschedule.request', 'appointment', $apptId, $patient['id']);
        if ($appt['doctor_id']) {
            notify((int) $appt['doctor_id'], 'Reschedule requested: ' . full_name($patient), 'Wants ' . fmt_date($date) . ($_POST['requested_time'] ?? '' ? ' at ' . fmt_time(post('requested_time')) : ''), url('index.php?page=reschedule'), (int) $appt['hospital_id'], (int) $appt['id']);
        }
        flash('success', 'Your request was sent. The hospital will confirm it soon.');
    } else {
        flash('error', 'Choose a valid future date.');
    }
    redirect('index.php?page=portal');
}
render_page('My appointments', 'portal', compact('patient', 'upcoming'));
