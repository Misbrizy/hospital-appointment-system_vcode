<?php
$u = require_login();
require_can('patients.write');
$hid = current_hospital_id();
$errors = [];

if (!within_patient_limit($hid)) {
    flash('warn', 'You have reached your plan\'s patient limit. Upgrade your plan to add more patients.');
    redirect('index.php?page=billing');
}

if (is_post()) {
    csrf_verify();
    $first = post('first_name'); $last = post('last_name');
    $dob = post('dob'); $gender = post('gender');
    $phone = normalize_phone(post('phone'));
    if ($first === '') { $errors[] = 'First name is required.'; }
    if ($last === '') { $errors[] = 'Last name is required.'; }
    if ($dob !== '' && !valid_date($dob)) { $errors[] = 'Enter a valid date of birth.'; }
    if (post('phone') !== '' && $phone === null) { $errors[] = 'Enter a valid phone number.'; }

    if (!$errors) {
        $data = [
            'hospital_id' => $hid, 'first_name' => $first, 'last_name' => $last,
            'dob' => $dob ?: null, 'gender' => $gender ?: 'female', 'phone' => $phone,
            'address' => post('address') ?: null, 'emergency_name' => post('emergency_name') ?: null,
            'emergency_phone' => normalize_phone(post('emergency_phone')), 'blood_group' => post('blood_group') ?: null,
            'insurance_provider' => post('insurance_provider') ?: null, 'insurance_number' => post('insurance_number') ?: null,
            'preferred_language' => post('preferred_language') ?: 'rw', 'sms_opt_in' => post('sms_opt_in') === '1' ? 1 : 0,
            'email' => post('email') ?: null, 'whatsapp_phone' => normalize_phone(post('whatsapp_phone')),
            'email_opt_in' => post('email_opt_in') === '1' ? 1 : 0, 'whatsapp_opt_in' => post('whatsapp_opt_in') === '1' ? 1 : 0,
            'created_by' => $u['id'], 'created_at' => now(), 'updated_at' => now(),
        ];
        $id = transaction(function () use ($hid, $data) {
            $seq = next_seq($hid, 'patient_seq');
            $data['patient_no'] = 'PT-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            return insert('patients', $data);
        });
        audit('patient.create', 'patient', $id, $id, ['name' => "$first $last"]);
        flash('success', 'Patient registered.');
        redirect('index.php?page=patient_view&id=' . $id);
    }
}
render_page('New patient', 'patient_form', ['errors' => $errors, 'patient' => null, 'action' => url('index.php?page=patient_new')]);
