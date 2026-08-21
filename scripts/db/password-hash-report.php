<?php

require_once __DIR__ . '/../../inc/database.php';
require_once __DIR__ . '/../../inc/password_hash_audit.php';

function password_hash_report_environment_value($name, $fallbackName = null)
{
    $value = getenv($name);

    if (($value === false || $value === '') && $fallbackName !== null) {
        $value = getenv($fallbackName);
    }

    return $value === false ? '' : (string)$value;
}

function password_hash_report_print_group($title, $summary)
{
    echo $title . ':' . PHP_EOL;
    echo '  Всего: ' . (int)$summary['total'] . PHP_EOL;
    echo '  Современный хеш: ' . (int)$summary['modern_hash'] . PHP_EOL;
    echo '  Остаётся MD5: ' . (int)$summary['legacy_md5'] . PHP_EOL;
    echo '  Без поддерживаемого хеша: ' . (int)$summary['without_supported_hash'] . PHP_EOL;
}

$config = array(
    'host' => password_hash_report_environment_value('TORI_PASSWORD_AUDIT_DB_HOST', 'TORI_MIGRATION_DB_HOST'),
    'port' => password_hash_report_environment_value('TORI_PASSWORD_AUDIT_DB_PORT', 'TORI_MIGRATION_DB_PORT'),
    'name' => password_hash_report_environment_value('TORI_PASSWORD_AUDIT_DB_NAME', 'TORI_MIGRATION_DB_NAME'),
    'user' => password_hash_report_environment_value('TORI_PASSWORD_AUDIT_DB_USER', 'TORI_MIGRATION_DB_USER'),
    'password' => password_hash_report_environment_value('TORI_PASSWORD_AUDIT_DB_PASS', 'TORI_MIGRATION_DB_PASS'),
);

foreach (array('host', 'name', 'user') as $requiredKey) {
    if ($config[$requiredKey] === '') {
        fwrite(STDERR, 'Missing database setting: ' . $requiredKey . PHP_EOL);
        exit(2);
    }
}

$config['port'] = $config['port'] === '' ? 3306 : (int)$config['port'];
$link = db_connect_silently($config['host'], $config['user'], $config['password'], $config['name'], $config['port']);

if (!$link) {
    fwrite(STDERR, 'Database connection failed: ' . db_connect_error() . PHP_EOL);
    exit(1);
}

db_set_charset($link, 'utf8mb4');
$summary = password_hash_audit_get_summary($link);

if ($summary === false) {
    fwrite(STDERR, 'Unable to build password hash report: ' . db_error($link) . PHP_EOL);
    db_close($link);
    exit(1);
}

echo 'Password hash report for database ' . $config['name'] . PHP_EOL;

if (!$summary['migration_applied']) {
    echo 'Migration 005_employee_password_hash.sql is not applied; modern hashes cannot be counted.' . PHP_EOL;
    db_close($link);
    exit(0);
}

password_hash_report_print_group('Активные учётные записи', $summary['active']);
password_hash_report_print_group('Архивные учётные записи', $summary['archived']);
echo 'This report is read-only. No passwords, login names, or hashes are displayed.' . PHP_EOL;

db_close($link);
