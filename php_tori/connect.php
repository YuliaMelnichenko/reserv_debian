<?php
$env = parse_ini_file(__DIR__ . '/../.env');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$link = mysqli_connect(
    $env['DB_HOST'],
    $env['DB_USER'],
    $env['DB_PASS'],
    $env['DB_NAME']
);

mysqli_set_charset($link, "utf8");

if ($link == false) {
    echo "Ошибка: Невозможно подключиться к MySQL " . mysqli_connect_error();
}
?>