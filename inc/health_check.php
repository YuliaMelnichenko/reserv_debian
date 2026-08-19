<?php

require_once __DIR__ . '/database.php';

function health_check_config($environment)
{
    if (!is_array($environment)) {
        return null;
    }

    $requiredKeys = array('DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME', 'HEALTH_CHECK_TOKEN');

    foreach ($requiredKeys as $key) {
        if (!isset($environment[$key]) || trim((string)$environment[$key]) === '') {
            return null;
        }
    }

    return array(
        'host' => (string)$environment['DB_HOST'],
        'user' => (string)$environment['DB_USER'],
        'password' => (string)$environment['DB_PASS'],
        'database' => (string)$environment['DB_NAME'],
        'port' => isset($environment['DB_PORT']) ? (int)$environment['DB_PORT'] : 3306,
        'token' => (string)$environment['HEALTH_CHECK_TOKEN'],
    );
}

function health_check_token_matches($expectedToken, $providedToken)
{
    return is_string($expectedToken)
        && $expectedToken !== ''
        && is_string($providedToken)
        && hash_equals($expectedToken, $providedToken);
}

function health_check_database_available($config)
{
    $link = db_connect_silently(
        $config['host'],
        $config['user'],
        $config['password'],
        $config['database'],
        $config['port']
    );

    if (!$link) {
        return false;
    }

    $result = db_query($link, 'SELECT 1');
    db_close($link);

    return $result !== false;
}
