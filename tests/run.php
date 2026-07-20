<?php

function test_assert_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function test_assert_same($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . '; expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}

$testFiles = array(
    __DIR__ . '/calendar_test.php',
    __DIR__ . '/time_format_test.php',
    __DIR__ . '/work_duration_test.php',
    __DIR__ . '/workday_period_test.php',
    __DIR__ . '/workday_state_test.php',
);

$failed = 0;

foreach ($testFiles as $testFile) {
    $test = require $testFile;
    $testName = basename($testFile);

    try {
        $test();
        echo "[OK] $testName" . PHP_EOL;
    } catch (Throwable $error) {
        $failed++;
        echo "[FAIL] $testName: " . $error->getMessage() . PHP_EOL;
    }
}

if ($failed > 0) {
    exit(1);
}

echo "All tests passed." . PHP_EOL;
