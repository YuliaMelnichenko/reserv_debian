<?php

require_once __DIR__ . '/../../inc/database.php';
require_once __DIR__ . '/../../inc/migrations.php';

function migration_script_environment_value($name, $fallbackName = null)
{
    $value = getenv($name);

    if (($value === false || $value === '') && $fallbackName !== null) {
        $value = getenv($fallbackName);
    }

    return $value === false ? '' : (string)$value;
}

function migration_script_usage()
{
    fwrite(STDERR, "Usage: php scripts/db/migrate.php --check|--dry-run|--apply --confirm=<database>\n");
}

$options = getopt('', array('check', 'dry-run', 'apply', 'confirm:'));
$selectedModes = array_filter(array(
    'check' => isset($options['check']),
    'dry-run' => isset($options['dry-run']),
    'apply' => isset($options['apply']),
));

if (count($selectedModes) !== 1) {
    migration_script_usage();
    exit(2);
}

try {
    $migrations = migration_catalog(__DIR__ . '/../../sql/migrations');
} catch (Throwable $error) {
    fwrite(STDERR, 'Migration catalog error: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

if (isset($selectedModes['check'])) {
    foreach ($migrations as $migration) {
        echo '[OK] ' . $migration['id'] . ' ' . $migration['file_name'] . PHP_EOL;
    }

    exit(0);
}

$config = array(
    'host' => migration_script_environment_value('TORI_MIGRATION_DB_HOST', 'TORI_TEST_DB_HOST'),
    'port' => migration_script_environment_value('TORI_MIGRATION_DB_PORT', 'TORI_TEST_DB_PORT'),
    'name' => migration_script_environment_value('TORI_MIGRATION_DB_NAME', 'TORI_TEST_DB_NAME'),
    'user' => migration_script_environment_value('TORI_MIGRATION_DB_USER', 'TORI_TEST_DB_USER'),
    'password' => migration_script_environment_value('TORI_MIGRATION_DB_PASS', 'TORI_TEST_DB_PASS'),
);

foreach (array('host', 'name', 'user') as $requiredKey) {
    if ($config[$requiredKey] === '') {
        fwrite(STDERR, 'Missing database setting: ' . $requiredKey . PHP_EOL);
        exit(2);
    }
}

$config['port'] = $config['port'] === '' ? 3306 : (int)$config['port'];

if (isset($selectedModes['apply']) && (!isset($options['confirm']) || $options['confirm'] !== $config['name'])) {
    fwrite(STDERR, 'Refusing to apply migrations without --confirm=' . $config['name'] . PHP_EOL);
    exit(2);
}

$link = db_connect_silently(
    $config['host'],
    $config['user'],
    $config['password'],
    $config['name'],
    $config['port']
);

if (!$link) {
    fwrite(STDERR, 'Database connection failed: ' . db_connect_error() . PHP_EOL);
    exit(1);
}

db_set_charset($link, 'utf8mb4');

try {
    if (isset($selectedModes['dry-run'])) {
        $pending = migrations_get_pending($link, $migrations);

        if ($pending === false) {
            throw new RuntimeException(db_error($link));
        }

        foreach ($pending as $migration) {
            echo '[PENDING] ' . $migration['id'] . ' ' . $migration['file_name'] . PHP_EOL;
        }

        if ($pending === array()) {
            echo 'No pending migrations.' . PHP_EOL;
        }
    } else {
        $applied = migrations_apply_pending($link, $migrations);

        if ($applied === false) {
            throw new RuntimeException(db_error($link));
        }

        foreach ($applied as $migration) {
            echo '[APPLIED] ' . $migration['id'] . ' ' . $migration['file_name'] . PHP_EOL;
        }

        if ($applied === array()) {
            echo 'No pending migrations.' . PHP_EOL;
        }
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Migration failed: ' . $error->getMessage() . PHP_EOL);
    db_close($link);
    exit(1);
}

db_close($link);
