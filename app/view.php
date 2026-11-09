<?php
declare(strict_types=1);

require_once __DIR__ . '/views/_icon.php';

/** Renders app/views/{name}.php with $vars extracted, returns the HTML as a string. */
function view(string $name, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_ROOT . "/app/views/{$name}.php";
    return (string) ob_get_clean();
}

/** Renders a view inside the logged-in app shell (sidebar + topbar). */
function render_page(string $title, string $viewName, array $vars = [], array $opts = []): void
{
    $u = current_user();
    $body = view($viewName, $vars);
    echo view('_shell', ['title' => $title, 'body' => $body, 'u' => $u, 'opts' => $opts]);
}

// Third element of each entry is now an i18n key (see app/lang/*.php), not literal text.
const NAV = [
    'hospital_admin' => [
        ['dashboard', 'nav.dashboard', 'grid'], ['patients', 'nav.patients', 'users'], ['appointments', 'nav.appointments', 'cal'],
        ['queue', 'nav.queue', 'clock'], ['anc', 'nav.anc', 'heart'], ['reschedule', 'nav.reschedule', 'refresh'],
        '—nav.group_comm', ['sms_templates', 'nav.sms_templates', 'msg'], ['reminder_rules', 'nav.reminder_rules', 'bell'], ['sms_logs', 'nav.sms_logs', 'send'],
        '—nav.group_hospital', ['users', 'nav.staff', 'id'], ['departments', 'nav.departments', 'layers'], ['reports', 'nav.reports', 'chart'],
        ['billing', 'nav.billing', 'card'], ['audit_logs', 'nav.audit_logs', 'shield'], ['settings', 'nav.settings', 'gear'],
        '—nav.group_finance', ['finance_dashboard', 'nav.finance', 'card'], ['invoices', 'nav.invoices', 'msg'], ['expenses', 'nav.expenses', 'send'],
    ],
    'doctor' => [
        ['dashboard', 'nav.dashboard', 'grid'], ['patients', 'nav.patients', 'users'], ['appointments', 'nav.my_appointments', 'cal'],
        ['queue', 'nav.queue', 'clock'], ['consultations', 'nav.consultations', 'shield'], ['anc', 'nav.anc', 'heart'], ['reschedule', 'nav.reschedule', 'refresh'], ['reports', 'nav.reports', 'chart'],
    ],
    'receptionist' => [
        ['dashboard', 'nav.dashboard', 'grid'], ['patients', 'nav.patients', 'users'], ['appointments', 'nav.appointments', 'cal'],
        ['queue', 'nav.queue', 'clock'], ['reschedule', 'nav.reschedule', 'refresh'], ['sms_logs', 'nav.sms_logs', 'send'],
    ],
    'nurse' => [
        ['dashboard', 'nav.dashboard', 'grid'], ['patients', 'nav.patients', 'users'], ['appointments', 'nav.appointments', 'cal'],
        ['queue', 'nav.queue', 'clock'], ['anc', 'nav.anc', 'heart'],
    ],
    'super_admin' => [
        ['dashboard', 'nav.dashboard', 'grid'], ['hospitals', 'nav.hospitals', 'building'], ['payments', 'nav.payments', 'card'],
        ['enterprise_requests', 'nav.enterprise_requests', 'msg'], ['plans', 'nav.plans', 'layers'], ['audit_logs', 'nav.audit_logs', 'shield'],
    ],
    'patient' => [
        ['portal', 'nav.my_bookings', 'cal'], ['portal_book', 'nav.book', 'plus'], ['portal_history', 'nav.history', 'clock'], ['notifications', 'nav.notifications', 'bell'],
    ],
    'finance' => [
        ['finance_dashboard', 'nav.finance', 'card'], ['invoices', 'nav.invoices', 'msg'], ['expenses', 'nav.expenses', 'send'], ['patients', 'nav.patients', 'users'],
    ],
];
