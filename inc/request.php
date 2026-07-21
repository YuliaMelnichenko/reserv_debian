<?php

require_once __DIR__ . '/date_range.php';

function request_has_scalar_value($source, $key)
{
    return is_array($source)
        && array_key_exists($key, $source)
        && $source[$key] !== null
        && is_scalar($source[$key]);
}

function request_scalar_value($source, $key, $default = null)
{
    if (!request_has_scalar_value($source, $key)) {
        return $default;
    }

    return $source[$key];
}

function request_int_value($source, $key, $default = 0)
{
    return (int)request_scalar_value($source, $key, $default);
}

function request_string_value($source, $key, $default = '')
{
    return (string)request_scalar_value($source, $key, $default);
}

function request_trimmed_string_value($source, $key, $default = '')
{
    return trim(request_string_value($source, $key, $default));
}

function request_date_value($source, $key)
{
    return normalize_date_value(request_string_value($source, $key));
}

function request_time_value($source, $key)
{
    return normalize_time_value(request_string_value($source, $key));
}

function request_datetime_value($source, $key)
{
    return normalize_datetime_value(request_string_value($source, $key));
}

function request_post_has($key)
{
    return request_has_scalar_value($_POST, $key);
}

function request_post_int($key, $default = 0)
{
    return request_int_value($_POST, $key, $default);
}

function request_post_string($key, $default = '')
{
    return request_string_value($_POST, $key, $default);
}

function request_post_trimmed_string($key, $default = '')
{
    return request_trimmed_string_value($_POST, $key, $default);
}

function request_post_date($key)
{
    return request_date_value($_POST, $key);
}

function request_post_time($key)
{
    return request_time_value($_POST, $key);
}

function request_post_datetime($key)
{
    return request_datetime_value($_POST, $key);
}
