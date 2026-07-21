<?php

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
