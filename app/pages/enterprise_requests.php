<?php
$u = require_role('super_admin');
if (is_post()) {
    csrf_verify();
    $id = (int) post('id');
    $status = post('status');
    if (in_array($status, ['new', 'contacted', 'closed'], true)) {
        update('enterprise_requests', ['status' => $status], 'id=?', [$id]);
        audit('enterprise.update', 'enterprise_request', $id);
    }
    redirect('index.php?page=enterprise_requests');
}
$requests = rows('SELECT er.*, h.name AS hospital_name, h.phone AS hospital_phone FROM enterprise_requests er JOIN hospitals h ON h.id=er.hospital_id ORDER BY er.created_at DESC');
render_page('Enterprise requests', 'enterprise_requests', compact('requests'));
