<?php
declare(strict_types=1);

/**
 * Doctor auto-assignment and slot-conflict checking.
 * Patients and reception never pick a doctor by hand for a NEW booking — the system
 * assigns the least-busy available doctor in the chosen department automatically.
 * A doctor can still be reassigned manually afterwards from the appointment edit page.
 */

const CLINIC_DAY_START = '08:00:00';
const CLINIC_DAY_END   = '17:00:00';
const CLINIC_SLOT_MINUTES = 30;

/** All bookable time slots in a working day, as "HH:MM:SS" strings. */
function clinic_day_slots(): array
{
    $slots = [];
    $t = strtotime(CLINIC_DAY_START);
    $end = strtotime(CLINIC_DAY_END);
    while ($t < $end) {
        $slots[] = date('H:i:s', $t);
        $t += CLINIC_SLOT_MINUTES * 60;
    }
    return $slots;
}

/** Active doctors in a hospital, optionally narrowed to one department. */
function doctors_in_department(int $hospitalId, ?int $departmentId): array
{
    if ($departmentId) {
        return rows("SELECT id, name FROM users WHERE hospital_id=? AND role='doctor' AND is_active=1 AND department_id=? ORDER BY id", [$hospitalId, $departmentId]);
    }
    return rows("SELECT id, name FROM users WHERE hospital_id=? AND role='doctor' AND is_active=1 ORDER BY id", [$hospitalId]);
}

/**
 * Finds the least-busy doctor in the department who is free at the exact date+time requested.
 * Returns the doctor row (id, name) or null if every doctor in the department is already
 * booked at that exact time (or the department has no doctors at all).
 */
function auto_assign_doctor(int $hospitalId, ?int $departmentId, string $date, string $time): ?array
{
    $doctors = doctors_in_department($hospitalId, $departmentId);
    if (!$doctors) {
        return null;
    }
    // Order candidates by how many appointments they already have that day (least busy first),
    // then pick the first one with no conflicting appointment at this exact time.
    $counts = [];
    foreach ($doctors as $d) {
        $counts[$d['id']] = (int) val(
            "SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appt_date=? AND status NOT IN ('cancelled')",
            [$d['id'], $date]
        );
    }
    usort($doctors, fn($a, $b) => $counts[$a['id']] <=> $counts[$b['id']]);

    foreach ($doctors as $d) {
        $conflict = val(
            "SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appt_date=? AND appt_time=? AND status NOT IN ('cancelled')",
            [$d['id'], $date, $time]
        );
        if ((int) $conflict === 0) {
            return $d;
        }
    }
    return null;
}

/**
 * Checks whether a department has any doctor free at all, anywhere in the working day,
 * on the given date. Used to tell the person "fully booked today" versus just
 * "that particular time is taken" when their first choice of time is unavailable.
 */
function department_fully_booked(int $hospitalId, ?int $departmentId, string $date): bool
{
    $doctors = doctors_in_department($hospitalId, $departmentId);
    if (!$doctors) {
        return false; // no doctors assigned to this department yet — let staff sort it out, don't block booking
    }
    foreach (clinic_day_slots() as $slot) {
        if (auto_assign_doctor($hospitalId, $departmentId, $date, $slot) !== null) {
            return false; // at least one free slot exists somewhere today
        }
    }
    return true;
}

/**
 * Attempts to auto-assign a doctor for a new booking. On failure, returns a clear,
 * human-readable reason so the booking form can tell the person exactly what to do next.
 * @return array{0: array|null, 1: string|null} [doctor row or null, error message or null]
 */
function try_auto_assign(int $hospitalId, ?int $departmentId, string $date, string $time): array
{
    $doctor = auto_assign_doctor($hospitalId, $departmentId, $date, $time);
    if ($doctor) {
        return [$doctor, null];
    }
    $doctors = doctors_in_department($hospitalId, $departmentId);
    if (!$doctors) {
        return [null, null]; // no doctors in this department yet — allow booking unassigned, staff will assign later
    }
    if (department_fully_booked($hospitalId, $departmentId, $date)) {
        return [null, 'Doctor in this department are fully booked for the entire day. Please choose another day.'];
    }
    return [null, 'That time slot has already been booked by onother patient. Please choose another time.'];
}

/** The department row if it exists in this hospital (active or not), else null. */
function department_in_hospital(?int $deptId, int $hospitalId): ?array
{
    if (!$deptId) {
        return null;
    }
    return row('SELECT id, name, is_active FROM departments WHERE id=? AND hospital_id=?', [$deptId, $hospitalId]);
}

/**
 * Makes an appointment's department and doctor agree with each other, using the database as the source of truth.
 * A doctor belongs to one department (users.department_id); an appointment shows that same department.
 *
 * @return array{0:?int,1:?int,2:?string} [department_id, doctor_id, error message or null]
 */
function resolve_department_and_doctor(int $hospitalId, ?int $deptId, ?int $doctorId): array
{
    if ($deptId && !department_in_hospital($deptId, $hospitalId)) {
        return [null, null, 'That department does not exist in this hospital.'];
    }
    if (!$doctorId) {
        return [$deptId ?: null, null, null];
    }
    $doc = row("SELECT u.id, u.name, u.department_id, d.name AS dept_name FROM users u LEFT JOIN departments d ON d.id=u.department_id
                WHERE u.id=? AND u.hospital_id=? AND u.role='doctor' AND u.is_active=1", [$doctorId, $hospitalId]);
    if (!$doc) {
        return [$deptId ?: null, null, 'Choose a valid, active doctor from this hospital.'];
    }
    if ($doc['department_id']) {
        if ($deptId && (int) $deptId !== (int) $doc['department_id']) {
            return [null, null, $doc['name'] . ' works in ' . $doc['dept_name'] . ', not in the department you selected. Choose a doctor from the same department.'];
        }
        return [(int) $doc['department_id'], (int) $doc['id'], null];
    }
    return [$deptId ?: null, (int) $doc['id'], null]; // doctor has no department on file yet: keep what the booking chose
}
