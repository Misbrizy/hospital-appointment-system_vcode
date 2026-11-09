<?php
/**
 * Applies pending, additive database migrations to an EXISTING installation without touching data.
 *   php bin/migrate.php            # apply migration_004 (idempotent; safe to re-run)
 *   php bin/migrate.php --status   # show what is / is not applied, change nothing
 *
 * On XAMPP (Windows):  C:\xampp\php\php.exe bin\migrate.php   (run inside the project folder)
 * Or paste database/migration_004.sql into phpMyAdmin > SQL. Take a backup first (see README).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Run this from the command line: php bin/migrate.php\n"); }

require dirname(__DIR__) . '/app/bootstrap.php';

function migration_004_applied(): bool
{
    return schema_missing() === [];
}

if (in_array('--status', $argv, true)) {
    echo 'migration_004: ' . (migration_004_applied() ? "applied\n" : "NOT applied\n");
    exit(0);
}

$file = dirname(__DIR__) . '/database/migration_004.sql';
$sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($file));
$failed = 0;
foreach (array_filter(array_map('trim', explode(";\n", $sql . "\n"))) as $stmt) {
    $stmt = rtrim($stmt, "; \t\r\n");
    if ($stmt === '') { continue; }
    try {
        db()->exec($stmt);
    } catch (Throwable $e) {
        $failed++;
        fwrite(STDERR, 'FAILED: ' . $e->getMessage() . "\n  in: " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 120) . "\n");
    }
}
@unlink(schema_cache_file());

// Heal hospitals created by older versions: add any missing default message template (never overwrites an edited
// one) and give a hospital with no reminder rules the standard three.
$addedT = 0; $addedR = 0;
try {
    foreach (rows('SELECT id FROM hospitals') as $h) {
        $addedT += ensure_default_templates((int) $h['id']);
        $addedR += ensure_default_reminder_rules((int) $h['id']);
    }
    echo "Message templates restored: $addedT; reminder rules created: $addedR\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Template check skipped: ' . $e->getMessage() . "\n");
}
echo $failed ? "Finished with $failed error(s) — fix the cause and run again (it is safe to re-run).\n" : "Migration 004 applied.\n";
echo 'Status: ' . (migration_004_applied() ? "all objects present\n" : "SOME OBJECTS MISSING\n");
exit($failed ? 1 : 0);
