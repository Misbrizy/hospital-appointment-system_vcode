<?php
$u = require_role('patient');
$patient = row('SELECT * FROM patients WHERE user_id=?', [$u['id']]);
if (!$patient) { abort(404); }
$total = (int) val("SELECT COUNT(*) FROM appointments WHERE patient_id=?", [$patient['id']]);
$p = paginate($total, 20);
$history = rows("SELECT a.*, d.name AS dept_name FROM appointments a LEFT JOIN departments d ON d.id=a.department_id WHERE a.patient_id=? ORDER BY a.appt_date DESC LIMIT {$p['per']} OFFSET {$p['offset']}", [$patient['id']]);
render_page('Appointment history', 'portal_history', compact('history', 'p'));
