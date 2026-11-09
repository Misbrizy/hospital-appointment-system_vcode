<?php
declare(strict_types=1);

error_reporting(E_ALL);
define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/helpers.php';

$__cfg = require APP_ROOT . '/config/config.php';
if ($__cfg['env'] === 'local') {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}
date_default_timezone_set($__cfg['timezone']);

require APP_ROOT . '/app/db.php';
require APP_ROOT . '/app/crypto.php';
require APP_ROOT . '/app/totp.php';
require APP_ROOT . '/app/security.php';
require APP_ROOT . '/app/audit.php';
require APP_ROOT . '/app/auth.php';
require APP_ROOT . '/app/smtp.php';
require APP_ROOT . '/app/whatsapp.php';
require APP_ROOT . '/app/reminders.php';
require APP_ROOT . '/app/sms.php';
require APP_ROOT . '/app/seed.php';
require APP_ROOT . '/app/appt_helpers.php';
require APP_ROOT . '/app/scheduling.php';
require APP_ROOT . '/app/i18n.php';
require APP_ROOT . '/app/limits.php';
require APP_ROOT . '/app/view.php';
require APP_ROOT . '/app/schema_guard.php';
require APP_ROOT . '/app/access.php';
require APP_ROOT . '/app/consultations.php';
require APP_ROOT . '/app/invoices.php';

set_exception_handler(function (Throwable $e) {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (cfg('env') === 'local') {
        echo '<pre style="white-space:pre-wrap;padding:2rem;font:13px/1.5 monospace">' . e($e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>';
        return;
    }
    http_response_code(500);
    render_error_page(500, 'Something went wrong', 'The error has been logged. Please try again, and contact support if it continues.');
});

force_https_if_needed();
if (PHP_SAPI !== 'cli') {
    send_security_headers();
    start_session();
    schema_guard();
    scheduler_web_kick_if_due();   // no-op unless the scheduler has been quiet for a while (see app/reminders.php)
}
