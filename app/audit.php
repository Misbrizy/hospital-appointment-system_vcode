<?php
declare(strict_types=1);

/** Who did what, when, to which patient. Failures here never break the user's request. */
function audit(string $action, ?string $entity = null, $entityId = null, ?int $patientId = null, array $details = [], ?array $user = null): void
{
    try {
        $u = $user ?? (function_exists('current_user') ? current_user() : null);
        q('INSERT INTO audit_logs (hospital_id, user_id, user_name, action, entity, entity_id, patient_id, details, ip, user_agent, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [
            $u['hospital_id'] ?? null,
            $u['id'] ?? null,
            $u['name'] ?? null,
            $action,
            $entity,
            $entityId,
            $patientId,
            $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            PHP_SAPI === 'cli' ? 'cli' : client_ip(),
            PHP_SAPI === 'cli' ? 'cli' : substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            now(),
        ]);
    } catch (Throwable $e) {
        error_log('audit failed: ' . $e->getMessage());
    }
}
