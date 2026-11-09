<?php
$u = require_role('super_admin');
if (is_post()) {
    csrf_verify();
    $id = (int) post('id');
    $h = row('SELECT * FROM hospitals WHERE id=?', [$id]);
    if ($h) {
        update('hospitals', ['status' => $h['status'] === 'active' ? 'suspended' : 'active'], 'id=?', [$id]);
        audit('hospital.toggle_status', 'hospital', $id);
    }
    redirect('index.php?page=hospitals');
}
$hospitals = rows('SELECT h.*, p.name AS plan_name, (SELECT COUNT(*) FROM users WHERE hospital_id=h.id) staff_count, (SELECT COUNT(*) FROM patients WHERE hospital_id=h.id AND deleted_at IS NULL) patient_count FROM hospitals h JOIN plans p ON p.id=h.plan_id ORDER BY h.created_at DESC');
render_page('Hospitals', 'hospitals', compact('hospitals'));
