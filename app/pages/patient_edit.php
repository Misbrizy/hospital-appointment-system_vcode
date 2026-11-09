<?php
$u = require_login();
require_can('patients.write');
$hid = current_hospital_id();
$id = (int) get('id');
$patient = scoped_or_404('patients', $id);
$errors = [];

if (is_post()) {
    csrf_verify();
    $first = post('first_name'); $last = post('last_name');
    $dob = post('dob');
    $phone = normalize_phone(post('phone'));
    if ($first === '') { $errors[] = 'First name is required.'; }
    if ($last === '') { $errors[] = 'Last name is required.'; }
    if ($dob !== '' && !valid_date($dob)) { $errors[] = 'Enter a valid date of birth.'; }
    if (post('phone') !== '' && $phone === null) { $errors[] = 'Enter a valid phone number.'; }

    if (!$errors) {
        update('patients', [
            'first_name' => $first, 'last_name' => $last, 'dob' => $dob ?: null, 'gender' => post('gender') ?: 'female',
            'phone' => $phone, 'address' => post('address') ?: null, 'emergency_name' => post('emergency_name') ?: null,
            'emergency_phone' => normalize_phone(post('emergency_phone')), 'blood_group' => post('blood_group') ?: null,
            'insurance_provider' => post('insurance_provider') ?: null, 'insurance_number' => post('insurance_number') ?: null,
            'preferred_language' => post('preferred_language') ?: 'rw', 'sms_opt_in' => post('sms_opt_in') === '1' ? 1 : 0,
            'email' => post('email') ?: null, 'whatsapp_phone' => normalize_phone(post('whatsapp_phone')),
            'email_opt_in' => post('email_opt_in') === '1' ? 1 : 0, 'whatsapp_opt_in' => post('whatsapp_opt_in') === '1' ? 1 : 0,
            'updated_at' => now(),
        ], 'id=? AND hospital_id=?', [$id, $hid]);
        audit('patient.update', 'patient', $id, $id);
        flash('success', 'Patient updated.');
        redirect('index.php?page=patient_view&id=' . $id);
    }
}
render_page('Edit patient', 'patient_form', ['errors' => $errors, 'patient' => $patient, 'action' => url('index.php?page=patient_edit&id=' . $id)]);
