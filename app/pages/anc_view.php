<?php
$u = require_login();
require_can('anc.view');
$hid = current_hospital_id();
$id = (int) get('id');
$preg = scoped_or_404('pregnancy_records', $id);
require_patient_access((int) $preg['patient_id']);
$patient = row('SELECT * FROM patients WHERE id=? AND hospital_id=?', [$preg['patient_id'], $hid]);
$visits = rows('SELECT * FROM anc_visits WHERE pregnancy_id=? ORDER BY visit_no', [$id]);

if (is_post()) {
    csrf_verify();
    $action = post('action');
    if ($action === 'book_visit' && can('anc.write')) {
        $visitId = (int) post('visit_id');
        $visit = row('SELECT * FROM anc_visits WHERE id=? AND pregnancy_id=?', [$visitId, $id]);
        if ($visit) {
            $apptId = transaction(function () use ($hid, $u, $patient, $visit) {
                $no = next_appt_no($hid);
                $aid = insert('appointments', [
                    'hospital_id' => $hid, 'appt_no' => $no, 'patient_id' => $patient['id'], 'appt_type' => 'Antenatal Care',
                    'appt_date' => $visit['planned_date'], 'appt_time' => '09:00:00', 'status' => 'scheduled',
                    'anc_visit_id' => $visit['id'], 'created_by' => $u['id'], 'created_at' => now(), 'updated_at' => now(),
                ]);
                update('anc_visits', ['status' => 'booked', 'appointment_id' => $aid], 'id=?', [$visit['id']]);
                return $aid;
            });
            $appt = row('SELECT * FROM appointments WHERE id=?', [$apptId]);
            send_appointment_sms('confirmation', $appt, $patient, $hid);
            queue_appointment_reminders($appt, $patient, $hid);
            audit('anc.visit_booked', 'anc_visit', $visitId, $patient['id']);
            flash('success', 'ANC visit booked as an appointment.');
        }
    } elseif ($action === 'set_risk' && can('anc.write')) {
        update('pregnancy_records', ['risk_level' => post('risk_level')], 'id=?', [$id]);
        audit('anc.risk_update', 'pregnancy_record', $id, $patient['id']);
        flash('success', 'Risk level updated.');
    }
    redirect('index.php?page=anc_view&id=' . $id);
}
render_page('Pregnancy record', 'anc_view', compact('preg', 'patient', 'visits'));
