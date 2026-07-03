<?php

function session_request_is_https()
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));

        if ($forwardedProto === 'https') {
            return true;
        }
    }

    return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
}

function app_cookie_options(
    $expires = 0,
    $path = '/',
    $httpOnly = true,
    $sameSite = 'Lax',
    $domain = ''
) {
    return array(
        'expires' => (int) $expires,
        'path' => $path,
        'domain' => $domain,
        'secure' => session_request_is_https(),
        'httponly' => (bool) $httpOnly,
        'samesite' => $sameSite,
    );
}

function start_app_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    $sessionCookieOptions = app_cookie_options();
    unset($sessionCookieOptions['expires']);
    session_set_cookie_params($sessionCookieOptions);
    session_start();
}

start_app_session();
