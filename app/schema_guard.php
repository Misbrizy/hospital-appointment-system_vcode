<?php
declare(strict_types=1);

/**
 * Detects an installation whose database has not yet received the latest additive migration, and says so
 * clearly — instead of failing later with an obscure "unknown column" error in the middle of a booking.
 * The result is cached in storage/schema.ok, so a healthy install pays one file_exists() per request.
 */

/** Objects migration_004 must have created: [table, column|null]. */
const SCHEMA_REQUIRED = [
    ['sms_logs', 'reminder_rule_id'], ['sms_logs', 'next_attempt_at'], ['sms_logs', 'last_attempt_at'], ['sms_logs', 'sending_started_at'],
    ['sms_attempts', null], ['notifications', 'appointment_id'],
    ['medical_records', 'consultation_no'], ['medical_records', 'consulted_at'], ['medical_records', 'department_id'], ['medical_records', 'addendum_of'],
    ['hospitals', 'consultation_seq'],
    ['invoices', 'subtotal'], ['invoices', 'tax_rate'], ['invoices', 'tax_amount'], ['invoices', 'other_charges'], ['invoice_items', null],
];

function schema_missing(): array
{
    $missing = [];
    foreach (SCHEMA_REQUIRED as [$table, $col]) {
        $n = $col === null
            ? (int) val('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?', [$table])
            : (int) val('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?', [$table, $col]);
        if ($n === 0) {
            $missing[] = $col === null ? $table : "$table.$col";
        }
    }
    return $missing;
}

function schema_cache_file(): string
{
    return APP_ROOT . '/storage/schema.ok';
}

function schema_is_current(): bool
{
    if (is_file(schema_cache_file())) {
        return true;
    }
    try {
        if (schema_missing()) {
            return false;
        }
    } catch (Throwable $e) {
        return true; // database unreachable etc. — let the normal error handling report that, not this guard
    }
    @file_put_contents(schema_cache_file(), date('c'));
    return true;
}

/** Called once per web request from bootstrap. Never runs for CLI (the migrator itself is a CLI script). */
function schema_guard(): void
{
    if (PHP_SAPI === 'cli' || schema_is_current()) {
        return;
    }
    http_response_code(503);
    $missing = e(implode(', ', schema_missing()));
    echo '<!doctype html><meta charset="utf-8"><title>Database update required</title>'
        . '<body style="font:16px/1.55 system-ui,sans-serif;max-width:720px;margin:8vh auto;padding:0 20px;color:#1b2b34">'
        . '<h1 style="font-size:1.4rem">Database update required</h1>'
        . '<p>This version of the system adds a few columns and tables (reminder retries, consultation records, invoice line items). '
        . 'Your existing data is not touched — the update only <strong>adds</strong> things.</p>'
        . '<p><strong>Option 1 (XAMPP):</strong> open a terminal in the project folder and run<br>'
        . '<code>C:\\xampp\\php\\php.exe bin\\migrate.php</code></p>'
        . '<p><strong>Option 2 (phpMyAdmin):</strong> select your database, open the <em>SQL</em> tab and paste the contents of '
        . '<code>database/migration_004.sql</code>.</p>'
        . '<p>Then reload this page. Missing: <code>' . $missing . '</code></p></body>';
    exit;
}
