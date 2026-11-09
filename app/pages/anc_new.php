<?php
$u = require_login();
require_can('anc.write');
$hid = current_hospital_id();
$patientId = (int) get('patient_id');
$patient = scoped_or_404('patients', $patientId);
require_patient_access($patientId);
$errors = [];

if (is_post()) {
    csrf_verify();
    $lmp = post('lmp_date');
    if (!valid_date($lmp)) { $errors[] = 'Enter a valid LMP date.'; }
    if (!$errors) {
        $edd = date('Y-m-d', strtotime($lmp . ' +280 days'));
        $id = transaction(function () use ($hid, $u, $patientId, $lmp, $edd) {
            $pid = insert('pregnancy_records', [
                'hospital_id' => $hid, 'patient_id' => $patientId, 'lmp_date' => $lmp, 'edd_date' => $edd,
                'previous_pregnancies' => (int) post('previous_pregnancies'), 'risk_level' => post('risk_level') ?: 'low',
                'status' => 'active', 'created_by' => $u['id'], 'created_at' => now(),
            ]);
            $weeks = [8, 16, 24, 28, 32, 36, 38, 40];
            foreach ($weeks as $i => $wk) {
                insert('anc_visits', [
                    'hospital_id' => $hid, 'pregnancy_id' => $pid, 'visit_no' => $i + 1, 'planned_week' => $wk,
                    'planned_date' => date('Y-m-d', strtotime($lmp . " +$wk weeks")), 'status' => 'planned',
                ]);
            }
            return $pid;
        });
        audit('anc.create', 'pregnancy_record', $id, $patientId);
        flash('success', 'Pregnancy record created with a full ANC visit schedule.');
        redirect('index.php?page=anc_view&id=' . $id);
    }
}
render_page('New pregnancy record', 'anc_form', compact('errors', 'patient'));
