<?php
$u = require_role('hospital_admin');
$hid = current_hospital_id();
$hospital = row('SELECT * FROM hospitals WHERE id=?', [$hid]);
$errors = [];
$testResult = null;

if (is_post()) {
    csrf_verify();
    $form = post('form');

    if ($form === 'smtp') {
        set_setting($hid, 'smtp_host', post('smtp_host'));
        set_setting($hid, 'smtp_port', post('smtp_port') ?: '587');
        set_setting($hid, 'smtp_encryption', post('smtp_encryption') ?: 'tls');
        set_setting($hid, 'smtp_username', post('smtp_username'));
        if (post('smtp_password') !== '') { set_setting($hid, 'smtp_password', (string) encrypt_field(post('smtp_password'))); }   // blank = keep the saved password
        set_setting($hid, 'smtp_from_email', post('smtp_from_email'));
        set_setting($hid, 'smtp_from_name', post('smtp_from_name') ?: cfg('app_name'));
        audit('hospital.smtp_update', 'hospital', $hid);
        flash('success', 'Email settings saved.');
        redirect('index.php?page=settings');
    } elseif ($form === 'whatsapp') {
        set_setting($hid, 'wa_twilio_sid', post('wa_twilio_sid'));
        if (post('wa_twilio_token') !== '') { set_setting($hid, 'wa_twilio_token', (string) encrypt_field(post('wa_twilio_token'))); }
        set_setting($hid, 'wa_twilio_from', post('wa_twilio_from'));
        audit('hospital.whatsapp_update', 'hospital', $hid);
        flash('success', 'WhatsApp settings saved.');
        redirect('index.php?page=settings');
    } elseif ($form === 'test_email') {
        $to = post('test_email_to');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email to send the test to.';
        } else {
            $cfg = hospital_smtp_config($hid);
            if (!$cfg) {
                $errors[] = 'Email is not configured yet. Save your SMTP settings first (or set MAIL_* in config/local.php).';
            } else {
                // Uses exactly the configuration real reminders use, so a passing test means reminders can be delivered.
                [$ok, $err] = smtp_send(
                    $cfg, $to, '',
                    'Test email from ' . $hospital['name'],
                    "This is a test message from your hospital's appointment system. If you received this, email reminders are working."
                );
                $testResult = $ok ? ['ok' => true, 'msg' => 'Test email sent — check the inbox.'] : ['ok' => false, 'msg' => $err];
            }
        }
    } elseif ($form === 'test_whatsapp') {
        $to = normalize_phone(post('test_wa_to'));
        if (!$to) {
            $errors[] = 'Enter a valid phone number to send the test to.';
        } else {
            $cfg = hospital_whatsapp_config($hid);
            if (!$cfg) {
                $errors[] = 'Save your WhatsApp settings first.';
            } else {
                [$ok, $id, $err] = whatsapp_send($cfg, $to, 'Test message from ' . $hospital['name'] . ' — your WhatsApp reminders are working.');
                $testResult = $ok ? ['ok' => true, 'msg' => 'Test WhatsApp message sent — check the phone.'] : ['ok' => false, 'msg' => $err];
            }
        }
    } else {
        $name = post('name');
        if ($name === '') { $errors[] = 'Hospital name is required.'; }
        if (!$errors) {
            update('hospitals', ['name' => $name, 'phone' => post('phone') ?: null, 'email' => post('email') ?: null, 'address' => post('address') ?: null, 'district' => post('district') ?: null], 'id=?', [$hid]);
            audit('hospital.settings_update', 'hospital', $hid);
            flash('success', 'Settings saved.');
            redirect('index.php?page=settings');
        }
    }
}
$smtp = hospital_smtp_config($hid);
$wa = hospital_whatsapp_config($hid);
render_page('Settings', 'settings', compact('hospital', 'errors', 'smtp', 'wa', 'testResult'));
