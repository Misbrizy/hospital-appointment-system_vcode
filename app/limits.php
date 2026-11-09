<?php
declare(strict_types=1);

/** Current subscription plan for a hospital, with usage against its limits. */
function plan_usage(int $hospitalId): array
{
    $h = row('SELECT hospitals.*, plans.* , hospitals.id AS id, plans.id AS plan_row_id FROM hospitals JOIN plans ON plans.id = hospitals.plan_id WHERE hospitals.id=?', [$hospitalId]);
    if (!$h) {
        return ['patients' => 0, 'sms_month' => 0, 'max_patients' => 0, 'max_sms_month' => 0, 'plan_name' => '—'];
    }
    $patients = (int) val('SELECT COUNT(*) FROM patients WHERE hospital_id=? AND deleted_at IS NULL', [$hospitalId]);
    $sms = (int) val("SELECT COUNT(*) FROM sms_logs WHERE hospital_id=? AND created_at >= ? AND status<>'cancelled'", [$hospitalId, date('Y-m-01')]);
    return [
        'patients' => $patients, 'max_patients' => $h['max_patients'],
        'sms_month' => $sms, 'max_sms_month' => $h['max_sms_month'],
        'plan_name' => $h['name'], 'is_trial' => (bool) $h['is_trial'], 'ends_at' => $h['subscription_ends_at'],
    ];
}

function within_patient_limit(int $hospitalId): bool
{
    $u = plan_usage($hospitalId);
    return $u['max_patients'] === null || $u['patients'] < (int) $u['max_patients'];
}

function within_sms_limit(int $hospitalId): bool
{
    $u = plan_usage($hospitalId);
    return $u['max_sms_month'] === null || $u['sms_month'] < (int) $u['max_sms_month'];
}
