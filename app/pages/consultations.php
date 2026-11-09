<?php
$u = require_login();
require_can('medical.view');
$hid = current_hospital_id();
$q = get('q');
$from = get('from'); $to = get('to');
$where = 'm.hospital_id=? AND m.addendum_of IS NULL';
$params = [$hid];
if (!can('medical.view_all')) { $where .= ' AND m.doctor_id=?'; $params[] = $u['id']; }   // a doctor's list is only their own consultations
if ($q !== '') { $where .= ' AND (p.patient_no LIKE ? OR CONCAT(p.first_name," ",p.last_name) LIKE ? OR m.consultation_no LIKE ?)'; $like = '%' . $q . '%'; array_push($params, $like, $like, $like); }
if ($from !== '' && valid_date($from)) { $where .= ' AND DATE(COALESCE(m.consulted_at, m.created_at)) >= ?'; $params[] = $from; }
if ($to !== '' && valid_date($to)) { $where .= ' AND DATE(COALESCE(m.consulted_at, m.created_at)) <= ?'; $params[] = $to; }
$total = (int) val("SELECT COUNT(*) FROM medical_records m JOIN patients p ON p.id=m.patient_id WHERE $where", $params);
$p = paginate($total, 20);
$records = rows("SELECT m.id, m.consultation_no, m.consulted_at, m.created_at, m.notes_enc, m.follow_up_date, p.first_name, p.last_name, p.patient_no, dep.name AS dept_name, doc.name AS doctor_name,
        (SELECT COUNT(*) FROM medical_records x WHERE x.addendum_of=m.id) AS addenda
    FROM medical_records m JOIN patients p ON p.id=m.patient_id LEFT JOIN departments dep ON dep.id=m.department_id LEFT JOIN users doc ON doc.id=m.doctor_id
    WHERE $where ORDER BY COALESCE(m.consulted_at, m.created_at) DESC LIMIT {$p['per']} OFFSET {$p['offset']}", $params);
render_page('Consultations', 'consultations', compact('records', 'p', 'q', 'from', 'to'));
