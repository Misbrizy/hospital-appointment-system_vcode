<?php
declare(strict_types=1);

/**
 * Record-level access control (who may open WHICH patient / appointment / consultation).
 *
 * config/permissions.php answers "may this ROLE use this feature at all?". This file answers the second
 * question the permission list cannot: "may THIS user open THIS record?" — in particular, a doctor may only
 * work with the patients and appointments that are assigned to them. Every page that loads a patient,
 * appointment or consultation by id calls one of the require_* functions below on the server, so changing
 * a URL or posting a hand-made request cannot reach someone else's data.
 */

/** Records the refusal in the audit log (who tried what), then shows the 403 page. */
function deny_access(string $what, ?int $entityId = null, ?int $patientId = null): void
{
    audit('access.denied', $what, $entityId, $patientId);
    abort(403, 'You do not have access to this record. It is not assigned to you.');
}

/**
 * SQL fragment limiting a patient query to the patients a doctor is allowed to see:
 * those with a (non-cancelled) appointment assigned to them, or whose consultation they wrote themselves.
 * Returns ['', []] for every other role (their reach is governed by role permissions + hospital scoping).
 *
 * @param string $patientIdCol the column holding the patient id in the surrounding query, e.g. 'p.id'
 * @return array{0:string,1:array}  [" AND (...)", params]
 */
function doctor_patient_scope(?array $u, string $patientIdCol): array
{
    if (!$u || $u['role'] !== 'doctor') {
        return ['', []];
    }
    $sql = " AND ($patientIdCol IN (SELECT da.patient_id FROM appointments da WHERE da.doctor_id = ? AND da.hospital_id = ? AND da.status <> 'cancelled')"
         . " OR $patientIdCol IN (SELECT mr.patient_id FROM medical_records mr WHERE mr.doctor_id = ? AND mr.hospital_id = ?))";
    return [$sql, [(int) $u['id'], (int) $u['hospital_id'], (int) $u['id'], (int) $u['hospital_id']]];
}

/** May this user open this patient's record? (hospital + role + doctor-assignment rules) */
function can_access_patient(int $patientId, ?array $u = null): bool
{
    $u = $u ?? current_user();
    if (!$u || $patientId <= 0) {
        return false;
    }
    if ($u['role'] === 'patient') {
        return (bool) val('SELECT COUNT(*) FROM patients WHERE id=? AND user_id=?', [$patientId, $u['id']]);
    }
    if ($u['hospital_id'] === null) {
        return false; // super admin has no patient access at all
    }
    $in = (int) val('SELECT COUNT(*) FROM patients WHERE id=? AND hospital_id=? AND deleted_at IS NULL', [$patientId, $u['hospital_id']]);
    if (!$in) {
        return false;
    }
    if ($u['role'] === 'doctor') {
        [$scope, $params] = doctor_patient_scope($u, 'patients.id');
        return (bool) val('SELECT COUNT(*) FROM patients WHERE id=? ' . $scope, array_merge([$patientId], $params));
    }
    return true;
}

function require_patient_access(int $patientId): void
{
    if (!can_access_patient($patientId)) {
        $u = current_user();
        // Same-hospital patient the doctor isn't assigned to => 403 (and logged); a patient from another hospital => 404 (don't confirm it exists).
        $sameHospital = $u && $u['hospital_id'] !== null && (int) val('SELECT COUNT(*) FROM patients WHERE id=? AND hospital_id=?', [$patientId, $u['hospital_id']]);
        if ($sameHospital) {
            deny_access('patient', $patientId, $patientId);
        }
        abort(404);
    }
}

/** May this user open/modify this appointment? Doctors: only their own. Patients: only theirs. Staff: their hospital. */
function can_access_appointment(array $appt, ?array $u = null): bool
{
    $u = $u ?? current_user();
    if (!$u) {
        return false;
    }
    if ($u['role'] === 'patient') {
        return (bool) val('SELECT COUNT(*) FROM patients WHERE id=? AND user_id=?', [$appt['patient_id'], $u['id']]);
    }
    if ($u['hospital_id'] === null || (int) $appt['hospital_id'] !== (int) $u['hospital_id']) {
        return false;
    }
    if ($u['role'] === 'doctor') {
        return (int) $appt['doctor_id'] === (int) $u['id'];
    }
    return true;
}

function require_appointment_access(array $appt): void
{
    if (!can_access_appointment($appt)) {
        deny_access('appointment', (int) $appt['id'], (int) $appt['patient_id']);
    }
}

/** Consultations are private to the doctor who wrote them (and to nobody else unless a role is granted medical.view_all). */
function can_view_consultation(array $rec, ?array $u = null): bool
{
    $u = $u ?? current_user();
    if (!$u || $u['hospital_id'] === null || (int) $rec['hospital_id'] !== (int) $u['hospital_id'] || !can('medical.view')) {
        return false;
    }
    if (can('medical.view_all')) {
        return true;
    }
    return (int) $rec['doctor_id'] === (int) $u['id'];
}

function require_consultation_access(array $rec): void
{
    if (!can_view_consultation($rec)) {
        deny_access('consultation', (int) $rec['id'], (int) $rec['patient_id']);
    }
}

/** The department an appointment really belongs to, read from the appointment + assigned doctor in the database. */
function appointment_department(array $appt): ?array
{
    if (!empty($appt['department_id'])) {
        $d = row('SELECT id, name FROM departments WHERE id=?', [$appt['department_id']]);
        if ($d) {
            return $d;
        }
    }
    if (!empty($appt['doctor_id'])) {
        return row('SELECT d.id, d.name FROM users u JOIN departments d ON d.id=u.department_id WHERE u.id=?', [$appt['doctor_id']]);
    }
    return null;
}
