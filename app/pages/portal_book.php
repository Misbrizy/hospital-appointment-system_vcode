<?php
$u = require_role('patient');
$patient = row('SELECT * FROM patients WHERE user_id=?', [$u['id']]);
if (!$patient) { abort(404, 'No patient record is linked to this account yet.'); }
$hid = (int) $patient['hospital_id'];
$departments = rows('SELECT * FROM departments WHERE hospital_id=? AND is_active=1 ORDER BY name', [$hid]);
$errors = [];

if (is_post()) {
    csrf_verify();
    $deptId = (int) post('department_id') ?: null;
    $date = post('appt_date'); $time = post('appt_time'); $type = post('appt_type');
    if (!$deptId || !department_in_hospital($deptId, $hid)) { $errors[] = 'Please select the department where you want to receive medical care.'; $deptId = null; }
    if (!valid_date($date) || $date < date('Y-m-d', strtotime('+1 day'))) { $errors[] = 'Please select a future date (at least tomorrow).'; }
    if (!valid_time($time)) { $errors[] = 'Please select an exact time.'; }
    if ($type === '') { $errors[] = 'Describe the medical condition you want to be treated for.'; }
    if (!within_sms_limit($hid)) { flash('warn', 'The hospital is approaching its monthly SMS message limit..'); }

    $assignedDoctor = null;
    if (!$errors) {
        [$assignedDoctor, $slotError] = try_auto_assign($hid, $deptId, $date, $time);
        if ($slotError) { $errors[] = $slotError; }
    }

    if (!$errors) {
        $doctorId = $assignedDoctor['id'] ?? null;
        $apptId = transaction(function () use ($hid, $patient, $date, $time, $type, $deptId, $doctorId) {
            $no = next_appt_no($hid);
            return insert('appointments', [
                'hospital_id' => $hid, 'appt_no' => $no, 'patient_id' => $patient['id'],
                'department_id' => $deptId, 'doctor_id' => $doctorId,
                'appt_type' => $type, 'appt_date' => $date, 'appt_time' => $time, 'status' => 'scheduled',
                'reminder_rule_ids' => implode(',', array_column(rows('SELECT id FROM reminder_rules WHERE hospital_id=? AND is_active=1', [$hid]), 'id')),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        $appt = row('SELECT * FROM appointments WHERE id=?', [$apptId]);
        send_appointment_sms('confirmation', $appt, $patient, $hid);
        queue_appointment_reminders($appt, $patient, $hid);
        if ($doctorId) {
            $dept = appointment_department($appt);
            notify($doctorId, 'New appointment request: ' . full_name($patient), ($dept ? $dept['name'] . ' · ' : '') . fmt_date($date) . ' at ' . fmt_time($time), url('index.php?page=appointment_edit&id=' . $apptId), $hid, $apptId);
        }
        audit('appointment.create', 'appointment', $apptId, $patient['id'], ['source' => 'portal', 'doctor_id' => $doctorId]);
        flash('success', $doctorId ? 'Your appointment has been successfully scheduled. You will be attended to by. ' . $assignedDoctor['name'] . '. We have sent you a confirmation message.' : 'Your appointment has been successfully scheduled. We have sent you a confirmation message.');
        redirect('index.php?page=portal');
    }
}
render_page('Book an Appointment.', 'portal_book', compact('errors', 'departments'));
