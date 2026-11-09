<?php
/**
 * JSON endpoint: which times are still free for a department on a given date.
 * Called by the booking forms' JavaScript whenever the department or date changes,
 * so the person never has to guess a time and find out afterwards that it's taken.
 */
require dirname(__DIR__) . '/app/bootstrap.php';
header('Content-Type: application/json');

$u = current_user();
if (!$u) { http_response_code(401); echo json_encode(['error' => 'login required']); exit; }

$hid = $u['role'] === 'patient'
    ? (int) (row('SELECT hospital_id FROM patients WHERE user_id=?', [$u['id']])['hospital_id'] ?? 0)
    : (int) $u['hospital_id'];
$deptId = (int) get('department_id') ?: null;
$date = get('date');

if (!$hid || !$deptId || !valid_date($date) || $date < date('Y-m-d')) {
    echo json_encode(['slots' => []]);
    exit;
}
$dept = row('SELECT id FROM departments WHERE id=? AND hospital_id=?', [$deptId, $hid]);
if (!$dept) { echo json_encode(['slots' => []]); exit; }

$doctorOnly = $u['role'] === 'doctor' ? (int) $u['id'] : null;

$out = [];
foreach (clinic_day_slots() as $slot) {
    if ($doctorOnly) {
        $taken = (int) val("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appt_date=? AND appt_time=? AND status NOT IN ('cancelled')", [$doctorOnly, $date, $slot]);
        $free = $taken === 0;
    } else {
        $free = auto_assign_doctor($hid, $deptId, $date, $slot) !== null;
    }
    $out[] = ['time' => substr($slot, 0, 5), 'free' => $free];
}
echo json_encode(['slots' => $out]);
