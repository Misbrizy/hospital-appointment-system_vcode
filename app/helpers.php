<?php
declare(strict_types=1);

function cfg(string $key, $default = null)
{
    static $c = null;
    if ($c === null) {
        $c = require APP_ROOT . '/config/config.php';
    }
    return $c[$key] ?? $default;
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return cfg('trust_proxy') && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function client_ip(): string
{
    if (cfg('trust_proxy') && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* ---------- URLs ---------- */

function base_path(): string
{
    static $b = null;
    if ($b !== null) {
        return $b;
    }
    if (PHP_SAPI === 'cli') {
        $u = (string) cfg('app_url');
        return $b = $u ? rtrim((string) parse_url($u, PHP_URL_PATH), '/') : '';
    }
    return $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $f = APP_ROOT . '/public/assets/' . ltrim($path, '/');
    $v = is_file($f) ? filemtime($f) : 1;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function abs_url(string $path = ''): string
{
    $app = (string) cfg('app_url');
    if ($app !== '') {
        $base = rtrim($app, '/');
    } else {
        $base = (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
    }
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $to): void
{
    if (!preg_match('#^https?://#i', $to)) {
        $to = url($to);
    }
    header('Location: ' . $to);
    exit;
}

/* ---------- Flash messages and request helpers ---------- */

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['t' => $type, 'm' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $k, string $d = ''): string
{
    $v = $_POST[$k] ?? $d;
    return is_string($v) ? trim($v) : $d;
}

function get(string $k, string $d = ''): string
{
    $v = $_GET[$k] ?? $d;
    return is_string($v) ? trim($v) : $d;
}

/** Value for re-filling a form: posted value if present, otherwise the fallback. */
function pv(string $k, $fallback = ''): string
{
    if (is_post() && isset($_POST[$k]) && is_string($_POST[$k])) {
        return e($_POST[$k]);
    }
    return e($fallback ?? '');
}

function selected($current, $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

function checked(bool $c): string
{
    return $c ? ' checked' : '';
}

function qs(array $merge = [], array $drop = []): string
{
    $q = array_merge($_GET, $merge);
    foreach ($drop as $d) {
        unset($q[$d]);
    }
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }
    return $q ? '?' . http_build_query($q) : '?';
}

/* ---------- Validation and formatting ---------- */

function valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}

function valid_time(string $s): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $s);
}

function normalize_phone(?string $raw): ?string
{
    if ($raw === null) {
        return null;
    }
    $p = preg_replace('/[\s\-\(\)\.]/', '', $raw);
    if ($p === '' || $p === null) {
        return null;
    }
    $cc = (string) cfg('default_country_code', '250');
    if (str_starts_with($p, '+')) {
        $digits = substr($p, 1);
    } elseif (str_starts_with($p, '00')) {
        $digits = substr($p, 2);
    } elseif (str_starts_with($p, '0')) {
        $digits = $cc . substr($p, 1);
    } elseif (strlen($p) === 9) {
        $digits = $cc . $p;
    } else {
        $digits = $p;
    }
    if (!ctype_digit($digits) || strlen($digits) < 9 || strlen($digits) > 15) {
        return null;
    }
    return '+' . $digits;
}

function fmt_date(?string $d, string $f = 'd M Y'): string
{
    return $d ? date($f, strtotime($d)) : '';
}

function fmt_time(?string $t): string
{
    return $t ? substr($t, 0, 5) : '';
}

function fmt_dt(?string $dt): string
{
    return $dt ? date('d M Y, H:i', strtotime($dt)) : '';
}

function money($n): string
{
    return number_format((float) $n) . ' ' . cfg('currency', 'RWF');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $s = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $s .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $s ?: '?';
}

function full_name(array $p): string
{
    return trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
}

const STATUS_LABELS = [
    'scheduled' => 'Scheduled', 'confirmed' => 'Confirmed', 'checked_in' => 'Waiting',
    'in_consultation' => 'With doctor', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'missed' => 'Missed',
    'pending' => 'Pending', 'sending' => 'Sending', 'sent' => 'Sent', 'delivered' => 'Delivered', 'failed' => 'Failed',
    'approved' => 'Approved', 'rejected' => 'Rejected', 'active' => 'Active', 'suspended' => 'Suspended',
    'planned' => 'Planned', 'booked' => 'Booked', 'attended' => 'Attended',
    'low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High',
    'delivered_preg' => 'Delivered', 'closed' => 'Closed', 'trial' => 'Trial', 'past_due' => 'Expired',
];

function badge(string $status, ?string $label = null): string
{
    if ($label === null) {
        $key = 'status.' . $status;
        $translated = t($key);
        $label = $translated !== $key ? $translated : (STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)));
    }
    return '<span class="badge s-' . e($status) . '">' . e($label) . '</span>';
}

function role_label(string $role): string
{
    $key = 'role.' . $role;
    $translated = t($key);
    return $translated !== $key ? $translated : $role;
}

/* ---------- Pagination ---------- */

function paginate(int $total, int $per = 20): array
{
    // "pg" (not "page") on purpose: "page" is already the router's own query param
    // (?page=patients, ?page=audit_logs...), so reusing it here for pagination would
    // silently overwrite the route on every "Next"/"Previous" click.
    $pages = max(1, (int) ceil($total / $per));
    $page = min($pages, max(1, (int) ($_GET['pg'] ?? 1)));
    return ['page' => $page, 'pages' => $pages, 'per' => $per, 'offset' => ($page - 1) * $per, 'total' => $total];
}

function pager(array $p): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $h = '<nav class="pager" aria-label="Pages">';
    if ($p['page'] > 1) {
        $h .= '<a href="' . e(qs(['pg' => $p['page'] - 1])) . '">Previous</a>';
    }
    $h .= '<span>Page ' . $p['page'] . ' of ' . $p['pages'] . '</span>';
    if ($p['page'] < $p['pages']) {
        $h .= '<a href="' . e(qs(['pg' => $p['page'] + 1])) . '">Next</a>';
    }
    return $h . '</nav>';
}

/* ---------- Hospital settings (key/value per hospital) ---------- */

function setting(int $hid, string $key, $default = null)
{
    static $cache = [];
    if (!isset($cache[$hid])) {
        $cache[$hid] = [];
        foreach (rows('SELECT `key`, `value` FROM hospital_settings WHERE hospital_id = ?', [$hid]) as $r) {
            $cache[$hid][$r['key']] = $r['value'];
        }
    }
    return $cache[$hid][$key] ?? $default;
}

function set_setting(int $hid, string $key, string $value): void
{
    q('INSERT INTO hospital_settings (hospital_id, `key`, `value`) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$hid, $key, $value]);
}
