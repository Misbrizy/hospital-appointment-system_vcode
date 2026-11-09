<?php
$u = require_login();
require_can('medical.write');
$hid = current_hospital_id();
$id = (int) get('id');
$appt = scoped_or_404('appointments', $id);
require_appointment_access($appt);   // a doctor can only record consultations for their own appointments

// Already recorded? Go to the record instead of creating a second one.
$existing = row('SELECT id FROM medical_records WHERE appointment_id=? AND addendum_of IS NULL ORDER BY id LIMIT 1', [$id]);
if ($existing) {
    redirect('index.php?page=consultation_view&id=' . $existing['id']);
}
// Recording happens while the patient is with the doctor; a visit already marked completed with no note may be written up late.
if (!in_array($appt['status'], ['checked_in', 'in_consultation', 'completed'], true)) {
    abort(403, 'This appointment is not in consultation. Check the patient in from the queue first.');
}
$patient = row('SELECT * FROM patients WHERE id=? AND hospital_id=?', [$appt['patient_id'], $hid]);
if (!$patient) { abort(404); }
$dept = appointment_department($appt);
// Previous consultations of THIS doctor with this patient (other doctors' notes are private to them).
$history = rows('SELECT m.id, m.consultation_no, m.consulted_at, m.created_at, m.notes_enc FROM medical_records m
    WHERE m.patient_id=? AND m.hospital_id=? AND m.doctor_id=? AND m.addendum_of IS NULL ORDER BY COALESCE(m.consulted_at, m.created_at) DESC LIMIT 5', [$patient['id'], $hid, $u['id']]);
$errors = [];

if (is_post()) {
    csrf_verify();
    [$sections, $vitals, $errors] = consultation_from_post();
    $followUp = post('follow_up_date');
    if ($followUp !== '' && !valid_date($followUp)) { $errors[] = 'Enter a valid follow-up date.'; }
    if (!$errors) {
        try {
            $recId = transaction(function () use ($hid, $patient, $appt, $u, $sections, $vitals, $followUp, $dept) {
                $rid = insert('medical_records', [
                    'hospital_id' => $hid, 'consultation_no' => next_consultation_no($hid), 'patient_id' => $patient['id'],
                    'appointment_id' => $appt['id'], 'doctor_id' => $u['id'], 'department_id' => $dept['id'] ?? null,
                    'consulted_at' => now(), 'notes_enc' => consultation_encode($sections, $vitals),
                    'follow_up_date' => $followUp ?: null, 'created_at' => now(),
                ]);
                update('appointments', ['status' => 'completed', 'completed_at' => $appt['completed_at'] ?? now(), 'updated_at' => now()], 'id=?', [$appt['id']]);
                return $rid;
            });
            audit('consultation.create', 'medical_record', $recId, (int) $patient['id']);
            flash('success', 'Consultation saved to the patient\'s record and the appointment marked completed.');
            redirect('index.php?page=consultation_view&id=' . $recId);
        } catch (Throwable $e) {
            error_log('consultation.create failed: ' . $e->getMessage());
            $errors[] = 'Could not save this consultation: ' . $e->getMessage();   // visible, so the write-up is never lost silently
        }
    }
}
render_page('Consultation — ' . full_name($patient), 'consultation', compact('appt', 'patient', 'history', 'errors', 'dept'));
