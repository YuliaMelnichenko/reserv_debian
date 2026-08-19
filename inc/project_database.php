<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/errors.php';

function project_database_connect(): mysqli
{
    $env = @parse_ini_file(__DIR__ . '/../.env');

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

    $link = db_connect_silently(
        $env['DB_HOST'],
        $env['DB_USER'],
        $env['DB_PASS'],
        $env['DB_NAME'],
        isset($env['DB_PORT']) ? (int)$env['DB_PORT'] : 3306
    );

    if (!$link) {
        echo application_error_message(
            'Database connection at ' . __FILE__ . ':' . __LINE__,
            db_connect_error()
        );
        exit;
    }

    db_set_charset($link, 'utf8');
    return $link;
}
