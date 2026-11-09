<?php
$u = require_login();
require_can('medical.view');
$hid = current_hospital_id();
$id = (int) get('id');
$rec = scoped_or_404('medical_records', $id);
if ($rec['addendum_of']) { redirect('index.php?page=consultation_view&id=' . $rec['addendum_of']); }   // addenda are shown under their record
require_consultation_access($rec);   // only the doctor who wrote it (or a role explicitly granted medical.view_all)

$patient = row('SELECT * FROM patients WHERE id=? AND hospital_id=?', [$rec['patient_id'], $hid]);
if (!$patient) { abort(404); }
$doctor = row('SELECT name, specialty FROM users WHERE id=?', [$rec['doctor_id']]);
$dept = $rec['department_id'] ? row('SELECT name FROM departments WHERE id=?', [$rec['department_id']]) : null;
$appt = $rec['appointment_id'] ? row('SELECT appt_no, appt_date, appt_time, appt_type FROM appointments WHERE id=?', [$rec['appointment_id']]) : null;

// Addendum: the author may add a dated note; the original stays untouched.
$errors = [];
if (is_post() && post('action') === 'addendum') {
    csrf_verify();
    if ((int) $rec['doctor_id'] !== (int) $u['id'] || !can('medical.write')) { deny_access('consultation addendum', $id, (int) $rec['patient_id']); }
    $text = mb_substr(trim((string) post('addendum')), 0, 5000);
    if ($text === '') {
        $errors[] = 'Write the addendum text first.';
    } else {
        $n = (int) val('SELECT COUNT(*) FROM medical_records WHERE addendum_of=?', [$id]) + 1;
        $aid = insert('medical_records', [
            'hospital_id' => $hid, 'consultation_no' => substr(($rec['consultation_no'] ?: 'REC' . $id) . '-A' . $n, 0, 20), 'patient_id' => $rec['patient_id'],
            'appointment_id' => $rec['appointment_id'], 'doctor_id' => $u['id'], 'department_id' => $rec['department_id'], 'consulted_at' => now(),
            'addendum_of' => $id, 'notes_enc' => (string) encrypt_field(json_encode(['_fmt' => 'addendum/1', 'text' => $text], JSON_UNESCAPED_UNICODE)), 'created_at' => now(),
        ]);
        audit('consultation.addendum', 'medical_record', $aid, (int) $rec['patient_id']);
        flash('success', 'Addendum added.');
        redirect('index.php?page=consultation_view&id=' . $id);
    }
}
audit('consultation.view', 'medical_record', $id, (int) $rec['patient_id']);
$content = consultation_decode($rec['notes_enc']);
$addenda = array_map(fn($a) => $a + ['text' => consultation_decode($a['notes_enc'])['addendum'] ?? ''],
    rows('SELECT m.*, d.name AS doctor_name FROM medical_records m LEFT JOIN users d ON d.id=m.doctor_id WHERE m.addendum_of=? ORDER BY m.id', [$id]));
render_page('Consultation ' . ($rec['consultation_no'] ?: '#' . $id), 'consultation_view', compact('rec', 'patient', 'doctor', 'dept', 'appt', 'content', 'addenda', 'errors'));
