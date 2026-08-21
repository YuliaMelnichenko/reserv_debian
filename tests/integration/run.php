<?php

require_once __DIR__ . '/bootstrap.php';

try {
    $config = integration_test_config();
} catch (Throwable $error) {
    fwrite(STDERR, '[CONFIG ERROR] ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

if ($config === null) {
    echo '[SKIP] MySQL integration tests: TORI_TEST_DB_* variables are not configured.' . PHP_EOL;
    exit(0);
}

$testFiles = array(
    __DIR__ . '/remember_token_test.php',
    __DIR__ . '/password_hash_migration_test.php',
    __DIR__ . '/workday_flow_test.php',
    __DIR__ . '/pause_remote_work_test.php',
    __DIR__ . '/presence_query_test.php',
    __DIR__ . '/accounting_trip_missing_data_test.php',
    __DIR__ . '/staff_leaves_archive_test.php',
    __DIR__ . '/notification_summary_integration_test.php',
    __DIR__ . '/notification_decision_test.php',
);
$failed = 0;
$testResults = array();
$link = null;

try {
    $link = integration_test_connect($config);

    foreach ($testFiles as $testFile) {
        $testName = basename($testFile);

        try {
            integration_test_reset_schema($link);
            integration_test_apply_migrations($link);
            $test = require $testFile;
            $test($link);
            $testResults[] = '[OK] ' . $testName;
        } catch (Throwable $error) {
            $failed++;
            $testResults[] = '[FAIL] ' . $testName . ': ' . $error->getMessage();
        }
    }
} catch (Throwable $error) {
    fwrite(STDERR, '[DATABASE ERROR] ' . $error->getMessage() . PHP_EOL);
    $failed++;
} finally {
    if ($link) {
        db_close($link);
    }
}

foreach ($testResults as $testResult) {
    echo $testResult . PHP_EOL;
}

if ($failed > 0) {
    exit(1);
}

echo 'All MySQL integration tests passed.' . PHP_EOL;
