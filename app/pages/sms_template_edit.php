<?php
$u = require_role('hospital_admin');
$hid = current_hospital_id();
$id = (int) get('id');
$tpl = scoped_or_404('sms_templates', $id);
$errors = [];
if (is_post()) {
    csrf_verify();
    $body = post('body');
    if ($body === '') { $errors[] = 'Message body cannot be empty.'; }
    if (mb_strlen($body) > 480) { $errors[] = 'Keep the template under 480 characters (about 3 SMS parts).'; }
    if (!$errors) {
        update('sms_templates', ['name' => post('name'), 'body' => $body], 'id=?', [$id]);
        audit('sms_template.update', 'sms_template', $id);
        flash('success', 'Template updated.');
        redirect('index.php?page=sms_templates');
    }
}
render_page('Edit template', 'sms_template_edit', compact('errors', 'tpl'));
