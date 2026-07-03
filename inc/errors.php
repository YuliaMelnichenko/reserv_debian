<?php

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
