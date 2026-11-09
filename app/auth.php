<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $u = false;
    if ($u !== false) {
        return $u;
    }
    if (empty($_SESSION['uid'])) {
        return $u = null;
    }
    $row = row('SELECT * FROM users WHERE id=? AND is_active=1', [$_SESSION['uid']]);
    if (!$row) {
        $_SESSION = [];
        return $u = null;
    }
    unset($row['password_hash'], $row['totp_secret']);
    return $u = $row;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login.php');
    }
    if (!empty($u['must_change_password']) && ($_GET['page'] ?? '') !== 'account') {
        flash('info', 'Please set a new password to continue.');
        redirect('index.php?page=account');
    }
    return $u;
}

function can(string $perm): bool
{
    $u = current_user();
    if (!$u) {
        return false;
    }
    static $perms = null;
    if ($perms === null) {
        $perms = require APP_ROOT . '/config/permissions.php';
    }
    return in_array($perm, $perms[$u['role']] ?? [], true);
}

function require_can(string $perm): void
{
    if (!can($perm)) {
        abort(403);
    }
}

function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        abort(403);
    }
    return $u;
}

/** Every data query in the app must be scoped to this hospital (super_admin operates hospital-less pages only). */
function current_hospital_id(): ?int
{
    $u = current_user();
    return $u ? ($u['hospital_id'] !== null ? (int) $u['hospital_id'] : null) : null;
}

/**
 * @return array{0:bool,1:string} [success, message]. On success the session is established
 * (or, if the account has 2FA on, $_SESSION['2fa_pending'] is set and the caller must show the code form).
 */
function attempt_login(string $identifier, string $password): array
{
    $ip = client_ip();
    if (!rate_limit_allow('login_ip:' . $ip, 20, 15)) {
        return [false, 'Too many attempts from this network. Please wait a few minutes and try again.'];
    }
    $identifier = trim($identifier);
    $col = str_contains($identifier, '@') ? 'email' : 'phone';
    $lookup = $col === 'phone' ? (normalize_phone($identifier) ?? $identifier) : $identifier;
    $u = row("SELECT * FROM users WHERE $col = ? AND is_active = 1", [$lookup]);

    if (!$u) {
        rate_limit_hit('login_user:' . strtolower($identifier));
        return [false, 'Incorrect phone/email or password.'];
    }
    if (!empty($u['locked_until']) && $u['locked_until'] > now()) {
        return [false, 'This account is temporarily locked because of repeated failed attempts. Try again later.'];
    }
    if (!password_verify($password, $u['password_hash'])) {
        $fails = (int) $u['failed_attempts'] + 1;
        $data = ['failed_attempts' => $fails];
        if ($fails >= (int) cfg('max_failed_logins', 5)) {
            $data['locked_until'] = date('Y-m-d H:i:s', time() + (int) cfg('lockout_minutes', 15) * 60);
            $data['failed_attempts'] = 0;
        }
        update('users', $data, 'id=?', [$u['id']]);
        return [false, 'Incorrect phone/email or password.'];
    }
    if (!empty($u['hospital_id'])) {
        $h = row('SELECT status FROM hospitals WHERE id=?', [$u['hospital_id']]);
        if (!$h || $h['status'] !== 'active') {
            return [false, 'This hospital\'s account is suspended. Please contact support.'];
        }
    }

    update('users', ['failed_attempts' => 0, 'locked_until' => null], 'id=?', [$u['id']]);

    if (!empty($u['totp_enabled'])) {
        session_regenerate_id(true);
        $_SESSION['2fa_pending'] = $u['id'];
        return [true, '2fa'];
    }
    finish_login($u);
    return [true, 'ok'];
}

function finish_login(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    unset($_SESSION['2fa_pending']);
    update('users', ['last_login_at' => now(), 'last_login_ip' => client_ip()], 'id=?', [$u['id']]);
    audit('login', 'user', $u['id'], null, [], $u);
}

function logout(): void
{
    $u = current_user();
    if ($u) {
        audit('logout', 'user', $u['id']);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Loads a hospital-scoped row by id, or 404s. Prevents cross-hospital access by construction. */
function scoped_or_404(string $table, int $id, ?int $hospitalId = null): array
{
    $hid = $hospitalId ?? current_hospital_id();
    $r = row("SELECT * FROM `$table` WHERE id=? AND hospital_id=?", [$id, $hid]);
    if (!$r) {
        abort(404);
    }
    return $r;
}
