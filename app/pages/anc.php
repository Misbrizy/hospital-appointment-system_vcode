<?php
$u = require_login();
require_can('anc.view');
$hid = current_hospital_id();
[$scopeSql, $scopeParams] = doctor_patient_scope($u, 'pr.patient_id');   // doctors: only their own patients' records
$records = rows("SELECT pr.*, p.first_name, p.last_name, p.patient_no FROM pregnancy_records pr JOIN patients p ON p.id=pr.patient_id WHERE pr.hospital_id=? AND pr.status='active'" . $scopeSql . " ORDER BY pr.edd_date", array_merge([$hid], $scopeParams));
render_page('Antenatal care', 'anc', compact('records'));
