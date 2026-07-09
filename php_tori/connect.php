<?php
require_once __DIR__ . '/../inc/errors.php';

$env = @parse_ini_file(__DIR__ . '/../.env');

mysqli_report(MYSQLI_REPORT_OFF);

if (
    !is_array($env)
    || !isset($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME'])
) {
    echo application_error_message(
        'Database configuration at ' . __FILE__ . ':' . __LINE__,
        'Required database settings are missing'
    );
    exit;
}

$link = mysqli_connect(
    $env['DB_HOST'],
    $env['DB_USER'],
    $env['DB_PASS'],
    $env['DB_NAME'],
    isset($env['DB_PORT']) ? (int) $env['DB_PORT'] : 3306
);

if ($link == false) {
    echo application_error_message(
        'Database connection at ' . __FILE__ . ':' . __LINE__,
        mysqli_connect_error()
    );
    exit;
}

mysqli_set_charset($link, "utf8");
?>
