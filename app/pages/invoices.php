<?php
$u = require_login();
require_can('finance.view');
$hid = current_hospital_id();
$status = get('status');
$where = 'i.hospital_id=?';
$params = [$hid];
if ($status !== '') { $where .= ' AND i.status=?'; $params[] = $status; }
$total = (int) val("SELECT COUNT(*) FROM invoices i WHERE $where", $params);
$p = paginate($total, 25);
$invoices = rows("SELECT i.*, pt.first_name, pt.last_name FROM invoices i JOIN patients pt ON pt.id=i.patient_id WHERE $where ORDER BY i.created_at DESC LIMIT {$p['per']} OFFSET {$p['offset']}", $params);
render_page('Invoices', 'invoices', compact('invoices', 'p', 'status'));
