<?php
$u = require_login();
require_can('audit.view');
$hid = current_hospital_id();
$where = $hid ? 'hospital_id=?' : '1=1';
$params = $hid ? [$hid] : [];
$total = (int) val("SELECT COUNT(*) FROM audit_logs WHERE $where", $params);
$p = paginate($total, 40);
$logs = rows("SELECT * FROM audit_logs WHERE $where ORDER BY created_at DESC LIMIT {$p['per']} OFFSET {$p['offset']}", $params);
render_page('Audit log', 'audit_logs', compact('logs', 'p'));
