<?php

require_once __DIR__ . '/../inc/health_check.php';

return function () {
    test_assert_same(null, health_check_config(null), 'Health check must reject a missing environment');
    test_assert_same(
        null,
        health_check_config(array('DB_HOST' => 'localhost')),
        'Health check must require database settings and a token'
    );

    $config = health_check_config(array(
        'DB_HOST' => 'mysql',
        'DB_USER' => 'tori',
        'DB_PASS' => 'secret',
        'DB_NAME' => 'tori_test',
        'DB_PORT' => '3307',
        'HEALTH_CHECK_TOKEN' => 'health-token',
    ));

    test_assert_same('mysql', $config['host'], 'Health check must preserve the database host');
    test_assert_same(3307, $config['port'], 'Health check must preserve the database port');
    test_assert_true(
        health_check_token_matches('health-token', 'health-token'),
        'Health check must accept the configured token'
    );
    test_assert_same(
        false,
        health_check_token_matches('health-token', 'wrong-token'),
        'Health check must reject an invalid token'
    );
};
