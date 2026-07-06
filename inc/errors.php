<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

function application_error_message($context, $details = '')
{
    $safeDetails = str_replace(array("\r", "\n"), ' ', (string) $details);
    error_log('[TORI] ' . $context . ($safeDetails !== '' ? ': ' . $safeDetails : ''));

    if (!headers_sent()) {
        http_response_code(500);
    }

    return 'Ошибка сервера';
}

function database_error_message($link, $context)
{
    $details = 'Unknown database error';

    if ($link instanceof mysqli) {
        $details = mysqli_error($link);
    }

    return application_error_message('Database error at ' . $context, $details);
}

function application_json_error($context, $details = '')
{
    return json_encode(
        array(
            'status' => 'error',
            'message' => application_error_message($context, $details),
        ),
        JSON_UNESCAPED_UNICODE
    );
}
