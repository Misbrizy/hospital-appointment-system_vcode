<?php
$u = require_login();
require_can('appointments.write');
$hid = current_hospital_id();
$id = (int) get('id');
$appt = scoped_or_404('appointments', $id);
require_appointment_access($appt);
$patient = row('SELECT * FROM patients WHERE id=?', [$appt['patient_id']]);
$errors = [];
$departments = rows('SELECT * FROM departments WHERE hospital_id=? AND is_active=1 ORDER BY name', [$hid]);
$doctors = rows("SELECT u.id, u.name, u.specialty, u.department_id, d.name AS dept_name FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE u.hospital_id=? AND u.role='doctor' AND u.is_active=1 ORDER BY u.name", [$hid]);
// The department shown for this appointment comes from the appointment/doctor records, never from list position.
$apptDept = appointment_department($appt);
$rules = [];

if (is_post()) {
    csrf_verify();
    $action = post('action', 'save');

    if ($action === 'cancel') {
        update('appointments', ['status' => 'cancelled', 'cancel_reason' => post('cancel_reason') ?: null, 'updated_at' => now()], 'id=?', [$id]);
        cancel_pending_reminders($id);
        send_appointment_sms('cancelled', $appt, $patient, $hid);
        audit('appointment.cancel', 'appointment', $id, $appt['patient_id']);
        flash('success', 'Appointment cancelled.');
        redirect('index.php?page=patient_view&id=' . $appt['patient_id']);
    } elseif ($action === 'complete') {
        update('appointments', ['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()], 'id=?', [$id]);
        audit('appointment.complete', 'appointment', $id, $appt['patient_id']);
        redirect('index.php?page=queue');
    } else {
        $date = post('appt_date'); $time = post('appt_time'); $type = post('appt_type');
        if (!valid_date($date)) { $errors[] = 'Choose a valid date.'; }
        if (!valid_time($time)) { $errors[] = 'Choose a valid time.'; }
        if ($type === '') { $errors[] = 'Appointment type is required.'; }

        if (!$errors) {
            $dateChanged = $date !== $appt['appt_date'] || $time !== $appt['appt_time'];
            // A doctor can move the date/time and add notes, but never reassign the
            // department or the doctor on the appointment — that stays with reception/admin.
            $data = [
                'appt_type' => $type, 'appt_date' => $date, 'appt_time' => $time,
                'notes' => post('notes') ?: null, 'updated_at' => now(),
            ];
            if ($u['role'] !== 'doctor') {
                [$newDept, $newDoc, $deptErr] = resolve_department_and_doctor($hid, (int) post('department_id') ?: null, (int) post('doctor_id') ?: null);
                if ($deptErr) { $errors[] = $deptErr; } else { $data['department_id'] = $newDept; $data['doctor_id'] = $newDoc; }
            }
        }
        if (!$errors) {
            update('appointments', $data, 'id=?', [$id]);
            if (($data['doctor_id'] ?? $appt['doctor_id']) && (int) ($data['doctor_id'] ?? 0) !== (int) $appt['doctor_id'] && isset($data['doctor_id'])) {
                $dept = appointment_department(array_merge($appt, $data));
                notify((int) $data['doctor_id'], 'Appointment assigned: ' . full_name($patient), ($dept ? $dept['name'] . ' · ' : '') . fmt_date($date) . ' at ' . fmt_time($time), url('index.php?page=appointment_edit&id=' . $id), $hid, $id);
            }
            if ($dateChanged) {
                cancel_pending_reminders($id);
                $fresh = row('SELECT * FROM appointments WHERE id=?', [$id]);
                queue_appointment_reminders($fresh, $patient, $hid, array_filter(array_map('intval', explode(',', (string) $appt['reminder_rule_ids']))) ?: null);
                send_appointment_sms('reschedule_approved', $fresh, $patient, $hid);
            }
            audit('appointment.update', 'appointment', $id, $appt['patient_id'], ['date_changed' => $dateChanged]);
            flash('success', 'Appointment updated.');
            redirect('index.php?page=patient_view&id=' . $appt['patient_id']);
        }
    }
}
render_page('Edit appointment', 'appointment_form', compact('errors', 'departments', 'doctors', 'rules', 'appt', 'apptDept') + ['preselectedPatient' => $patient, 'action' => url('index.php?page=appointment_edit&id=' . $id)]);
