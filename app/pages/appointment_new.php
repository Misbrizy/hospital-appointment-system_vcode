<?php
$u = require_login();
require_can('appointments.write');
$hid = current_hospital_id();
$errors = [];
$patientId = (int) get('patient_id');
$preselectedPatient = ($patientId && can_access_patient($patientId)) ? row('SELECT * FROM patients WHERE id=? AND hospital_id=?', [$patientId, $hid]) : null;
$isDoctorBooking = $u['role'] === 'doctor';

// A doctor booking a patient for themselves never picks a department — it's locked to
// whichever department(s) their account was approved for when it was set up.
$departments = $isDoctorBooking
    ? rows('SELECT * FROM departments WHERE hospital_id=? AND id=?', [$hid, $u['department_id']])
    : rows('SELECT * FROM departments WHERE hospital_id=? AND is_active=1 ORDER BY name', [$hid]);
$rules = rows('SELECT * FROM reminder_rules WHERE hospital_id=? AND is_active=1 ORDER BY days_before DESC', [$hid]);

if (is_post()) {
    csrf_verify();
    $pid = (int) post('patient_id');
    // A doctor can only book patients who are already assigned to them; reception books everyone else.
    $patient = can_access_patient($pid) ? row('SELECT * FROM patients WHERE id=? AND hospital_id=?', [$pid, $hid]) : null;
    $date = post('appt_date'); $time = post('appt_time'); $type = post('appt_type');
    $deptId = $isDoctorBooking ? ((int) $u['department_id'] ?: null) : ((int) post('department_id') ?: null);
    if ($isDoctorBooking && !$deptId) { $errors[] = 'Your account has no department yet. Ask your hospital administrator to assign one.'; }
    if (!$isDoctorBooking && !$errors && $deptId && !department_in_hospital($deptId, $hid)) { $errors[] = 'Choose a valid department.'; }
    if (!$patient) { $errors[] = 'Choose a valid patient.'; }
    if (!valid_date($date) || $date < date('Y-m-d')) { $errors[] = 'Choose a valid, future appointment date.'; }
    if (!valid_time($time)) { $errors[] = 'Choose a valid time.'; }
    if ($type === '') { $errors[] = 'Appointment type is required.'; }
    if (!within_sms_limit($hid)) { flash('warn', 'You are near or over your monthly SMS limit — reminders may not send until next month or you upgrade.'); }

    $doctorId = null;
    $assignedDoctorName = null;
    if (!$errors) {
        if ($isDoctorBooking) {
            // The doctor is booking for themselves: just check THEY are free at that time.
            $conflict = (int) val("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appt_date=? AND appt_time=? AND status NOT IN ('cancelled')", [$u['id'], $date, $time]);
            if ($conflict > 0) {
                $errors[] = 'Ufite indi gahunda kuri iyo saha. Hitamo indi saha.';
            } else {
                $doctorId = (int) $u['id'];
                $assignedDoctorName = $u['name'];
            }
        } else {
            [$assignedDoctor, $slotError] = try_auto_assign($hid, $deptId, $date, $time);
            if ($slotError) { $errors[] = $slotError; }
            $doctorId = $assignedDoctor['id'] ?? null;
            $assignedDoctorName = $assignedDoctor['name'] ?? null;
        }
    }

    if (!$errors) {
        // Only the rules ticked on the form (none ticked = none wanted). The form is the only place that omits `rules`
        // entirely when nothing is ticked, so tell that apart from "field not present at all".
        $ruleIds = isset($_POST['rules']) ? array_map('intval', (array) $_POST['rules']) : [];
        $ruleIds = array_values(array_intersect($ruleIds, array_map('intval', array_column($rules, 'id'))));
        $apptId = transaction(function () use ($hid, $u, $pid, $date, $time, $type, $deptId, $doctorId, $ruleIds) {
            $no = next_appt_no($hid);
            return insert('appointments', [
                'hospital_id' => $hid, 'appt_no' => $no, 'patient_id' => $pid,
                'department_id' => $deptId, 'doctor_id' => $doctorId,
                'appt_type' => $type, 'appt_date' => $date, 'appt_time' => $time, 'status' => 'scheduled',
                'notes' => post('notes') ?: null, 'reminder_rule_ids' => implode(',', $ruleIds),
                'created_by' => $u['id'], 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        $appt = row('SELECT * FROM appointments WHERE id=?', [$apptId]);
        send_appointment_sms('confirmation', $appt, $patient, $hid);
        if ($ruleIds) { queue_appointment_reminders($appt, $patient, $hid, $ruleIds); }
        if ($doctorId && $doctorId !== (int) $u['id']) {
            $dept = appointment_department($appt);
            notify($doctorId, 'New appointment: ' . full_name($patient), ($dept ? $dept['name'] . ' · ' : '') . fmt_date($date) . ' at ' . fmt_time($time), url('index.php?page=appointment_edit&id=' . $apptId), $hid, $apptId);
        }
        audit('appointment.create', 'appointment', $apptId, $pid, ['type' => $type, 'date' => $date, 'doctor_id' => $doctorId]);
        flash('success', $doctorId ? 'Appointment booked with ' . $assignedDoctorName . '. Confirmation SMS queued.' : 'Appointment booked. Confirmation SMS queued.');
        redirect('index.php?page=patient_view&id=' . $pid);
    }
}
render_page('Book appointment', 'appointment_form', compact('errors', 'departments', 'rules', 'preselectedPatient', 'isDoctorBooking') + ['appt' => null, 'isNew' => true, 'action' => url('index.php?page=appointment_new')]);
