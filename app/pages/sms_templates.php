<?php
$u = require_role('hospital_admin');
$hid = current_hospital_id();
$templates = rows('SELECT * FROM sms_templates WHERE hospital_id=? ORDER BY code, language', [$hid]);
render_page('SMS templates', 'sms_templates', compact('templates'));
