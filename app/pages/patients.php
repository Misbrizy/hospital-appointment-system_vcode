<?php
$u = require_login();
require_can('patients.view');
$hid = current_hospital_id();
$search = get('q');
$where = 'hospital_id=? AND deleted_at IS NULL';
$params = [$hid];
// Doctors only ever list their own patients (assigned appointments / consultations they wrote).
[$scopeSql, $scopeParams] = doctor_patient_scope($u, 'id');
$where .= $scopeSql;
$params = array_merge($params, $scopeParams);
if ($search !== '') {
    $where .= ' AND (patient_no LIKE ? OR CONCAT(first_name," ",last_name) LIKE ? OR phone LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
$total = (int) val("SELECT COUNT(*) FROM patients WHERE $where", $params);
$p = paginate($total, 20);
$patients = rows("SELECT * FROM patients WHERE $where ORDER BY created_at DESC LIMIT {$p['per']} OFFSET {$p['offset']}", $params);
render_page('Patients', 'patients', compact('patients', 'p', 'search'));
