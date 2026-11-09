<?php
/**
 * One-time installer: run from the command line inside the gahunda/ folder.
 *   php bin/install.php
 * Asks for database details, creates config/local.php with a fresh APP_KEY, imports the schema,
 * and creates the first super-admin account. Safe to re-run: it will not overwrite an existing config/local.php.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Run this from the command line: php bin/install.php\n"); }

$root = dirname(__DIR__);
$localConfig = $root . '/config/local.php';

function ask(string $prompt, string $default = ''): string
{
    echo $prompt . ($default !== '' ? " [$default]" : '') . ': ';
    $line = trim((string) fgets(STDIN));
    return $line !== '' ? $line : $default;
}

if (is_file($localConfig)) {
    echo "config/local.php already exists — installer will only (re)import the database schema.\n";
} else {
    echo "=== Gahunda installer ===\n";
    $dbHost = ask('Database host', '127.0.0.1');
    $dbPort = ask('Database port', '3306');
    $dbName = ask('Database name', 'hospital-appointment');
    $dbUser = ask('Database user', 'root');
    $dbPass = ask('Database password', '');
    $appUrl = ask('Public URL of this app (e.g. https://gahunda.example.rw)', '');

    $appKey = base64_encode(random_bytes(32));
    $conf = "<?php\nreturn [\n"
        . "    'db_host' => " . var_export($dbHost, true) . ",\n"
        . "    'db_port' => " . (int) $dbPort . ",\n"
        . "    'db_name' => " . var_export($dbName, true) . ",\n"
        . "    'db_user' => " . var_export($dbUser, true) . ",\n"
        . "    'db_pass' => " . var_export($dbPass, true) . ",\n"
        . "    'app_url' => " . var_export($appUrl, true) . ",\n"
        . "    'app_key' => " . var_export($appKey, true) . ",\n"
        . "    'env' => 'production',\n"
        . "];\n";
    file_put_contents($localConfig, $conf);
    echo "Wrote config/local.php\n";
}

require $root . '/app/bootstrap.php';

echo "Importing database schema...\n";
$sql = file_get_contents($root . '/database/schema.sql');
// Strip whole-line SQL comments first so a leading "-- comment" doesn't cause a real
// statement below it to be mistaken for a comment and skipped.
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
    if ($stmt === '') { continue; }
    try { db()->exec($stmt); } catch (Throwable $e) { fwrite(STDERR, 'Schema warning: ' . $e->getMessage() . "\n"); }
}
echo "Schema imported.\n";

$existing = (int) val("SELECT COUNT(*) FROM users WHERE role='super_admin'");
if ($existing === 0) {
    echo "\n=== Create the super-admin account (manages all hospitals) ===\n";
    $name = ask('Full name', 'Super Admin');
    $email = ask('Email', 'admin@example.com');
    $phone = ask('Phone', '0780000000');
    $pw = ask('Password (min 8 chars, letters+numbers)', '');
    while (password_error($pw)) { echo "Weak password. "; $pw = ask('Password'); }
    insert('users', [
        'hospital_id' => null, 'role' => 'super_admin', 'name' => $name, 'email' => $email,
        'phone' => normalize_phone($phone), 'password_hash' => hash_password($pw), 'is_active' => 1, 'created_at' => now(),
    ]);
    echo "Super-admin created. Sign in at your app URL + /login.php\n";
} else {
    echo "A super-admin already exists — skipping.\n";
}

echo "\nDone. Next steps:\n";
echo "1. Point your web server's document root at the public/ folder.\n";
echo "2. Set SMS_DRIVER=africastalking and AT_USERNAME/AT_API_KEY as environment variables when ready to send real SMS (the default 'log' driver just writes messages to storage/logs).\n";
echo "3. Add cron/send_sms.php to cron to run every 1-5 minutes, and cron/mark_missed.php hourly.\n";
