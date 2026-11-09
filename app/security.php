<?php
declare(strict_types=1);

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function force_https_if_needed(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    if (cfg('force_https') && !is_https()) {
        header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('gahunda_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    $idle = (int) cfg('session_idle_minutes', 30) * 60;
    if (!empty($_SESSION['uid']) && isset($_SESSION['last_active']) && time() - $_SESSION['last_active'] > $idle) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['flash'][] = ['t' => 'info', 'm' => 'You were signed out after a period of inactivity.'];
    }
    $_SESSION['last_active'] = time();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        render_error_page(419, 'Your session expired', 'Go back, refresh the page and try again.');
        exit;
    }
}

/* ---------- Rate limiting (database backed, works on shared hosting) ---------- */

function rate_limit_hit(string $bucket): void
{
    q('INSERT INTO rate_limits (bucket, created_at) VALUES (?, ?)', [substr($bucket, 0, 190), now()]);
}

function rate_limit_count(string $bucket, int $minutes): int
{
    return (int) val('SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND created_at > ?', [substr($bucket, 0, 190), date('Y-m-d H:i:s', time() - $minutes * 60)]);
}

/** Returns true (and records a hit) if the caller is still under the limit. */
function rate_limit_allow(string $bucket, int $max, int $minutes): bool
{
    if (rate_limit_count($bucket, $minutes) >= $max) {
        return false;
    }
    rate_limit_hit($bucket);
    return true;
}

/* ---------- Passwords ---------- */

function hash_password(string $pw): string
{
    return password_hash($pw, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
}

function password_error(string $pw): ?string
{
    if (strlen($pw) < 8) {
        return 'Use at least 8 characters.';
    }
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) {
        return 'Use a mix of letters and numbers.';
    }
    return null;
}

function random_password(int $len = 10): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out . random_int(2, 9);
}

function render_error_page(int $code, string $title, string $msg): void
{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . '</title>'
        . '<link rel="stylesheet" href="' . e(asset('css/app.css')) . '"></head><body class="auth-body"><main class="auth-card"><h1>' . e($title) . '</h1><p>' . e($msg) . '</p>'
        . '<p><a class="btn" href="' . e(url('index.php')) . '">Back to start</a></p></main></body></html>';
}

function abort(int $code, string $msg = ''): void
{
    http_response_code($code);
    $titles = [403 => 'You don\'t have access to this page', 404 => 'Page not found', 419 => 'Session expired', 500 => 'Something went wrong'];
    $defaults = [
        403 => 'Your role does not include this area. If you think this is a mistake, ask your hospital administrator.',
        404 => 'The page or record you asked for does not exist, or belongs to another hospital.',
    ];
    render_error_page($code, $titles[$code] ?? 'Error', $msg ?: ($defaults[$code] ?? 'Please try again.'));
    exit;
}
