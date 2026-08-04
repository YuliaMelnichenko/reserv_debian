<?php

require_once __DIR__ . '/errors.php';

function ajax_response_headers($contentType = 'text/plain')
{
    if (!headers_sent()) {
        header('Content-Type: ' . $contentType . '; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

function ajax_text_headers()
{
    ajax_response_headers('text/plain');
}

function ajax_json_headers()
{
    ajax_response_headers('application/json');
}

function ajax_encode_json($payload)
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    if ($json === false) {
        return '{"status":"error","message":"Ошибка сервера"}';
    }

    return $json;
}

function ajax_text_response($payload, $statusCode = null)
{
    if ($statusCode !== null && !headers_sent()) {
        http_response_code((int)$statusCode);
    }

    ajax_text_headers();
    echo (string)$payload;
}

function ajax_json_response($payload, $statusCode = null)
{
    if ($statusCode !== null && !headers_sent()) {
        http_response_code((int)$statusCode);
    }

    ajax_json_headers();
    echo ajax_encode_json($payload);
}

function ajax_json_application_error($context, $details = '')
{
    $message = application_error_message($context, $details);
    ajax_json_response(array('status' => 'error', 'message' => $message), 500);
}

function ajax_database_error($link, $context)
{
    echo ajax_database_error_message($link, $context);
}

function ajax_database_error_message($link, $context)
{
    if (!headers_sent()) {
        http_response_code(500);
    }

    ajax_text_headers();
    return database_error_message($link, $context);
}
