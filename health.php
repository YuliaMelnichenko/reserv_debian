<?php

require_once __DIR__ . '/inc/health_check.php';

header('Content-Type: application/json; charset=utf-8');

$environment = @parse_ini_file(__DIR__ . '/.env');
$config = health_check_config($environment);

if ($config === null) {
    error_log('[TORI] Health check configuration is incomplete');
    http_response_code(503);
    echo json_encode(array('status' => 'unavailable'));
    exit;
}

$providedToken = isset($_SERVER['HTTP_X_TORI_HEALTH_TOKEN'])
    ? (string)$_SERVER['HTTP_X_TORI_HEALTH_TOKEN']
    : '';

if (!health_check_token_matches($config['token'], $providedToken)) {
    http_response_code(404);
    echo json_encode(array('status' => 'not_found'));
    exit;
}

if (!health_check_database_available($config)) {
    error_log('[TORI] Health check database connection failed');
    http_response_code(503);
    echo json_encode(array('status' => 'unavailable'));
    exit;
}

echo json_encode(array('status' => 'ok'));
