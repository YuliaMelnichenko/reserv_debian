<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function access_session_is_valid()
{
    return isset($_SESSION['ss_id'], $_SESSION['ss_sessid'])
        && hash_equals((string) $_SESSION['ss_sessid'], session_id());
}

function deny_ajax_access($statusCode, $message)
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo $message;
    exit;
}

function require_ajax_auth()
{
    if (!access_session_is_valid()) {
        deny_ajax_access(401, 'AUTH_REQUIRED');
    }
}

function access_current_user_is_director()
{
    return isset($_SESSION['ss_id'])
        && in_array((int) $_SESSION['ss_id'], array(500, 501), true);
}

function access_current_user_is_superuser()
{
    if (access_current_user_is_director()) {
        return true;
    }

    $env = parse_ini_file(__DIR__ . '/../.env');
    $link = mysqli_connect(
        $env['DB_HOST'],
        $env['DB_USER'],
        $env['DB_PASS'],
        $env['DB_NAME']
    );
    mysqli_set_charset($link, 'utf8');

    $userID = (int) $_SESSION['ss_id'];
    $stmt = mysqli_prepare(
        $link,
        'SELECT 1 FROM GROUPS WHERE SUPERVISORID = ? AND TYPE <> -1 LIMIT 1'
    );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $allowed = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    mysqli_close($link);

    return $allowed;
}

function require_ajax_superuser()
{
    require_ajax_auth();

    if (!access_current_user_is_superuser()) {
        deny_ajax_access(403, 'FORBIDDEN');
    }
}

function require_ajax_self_or_superuser($targetUserID)
{
    require_ajax_auth();

    if ((int) $targetUserID === (int) $_SESSION['ss_id']) {
        return;
    }

    if (!access_current_user_is_superuser()) {
        deny_ajax_access(403, 'FORBIDDEN');
    }
}

function require_page_auth()
{
    if (!access_session_is_valid()) {
        header('Location: auth.php');
        exit;
    }
}

function require_page_director()
{
    require_page_auth();

    if (!access_current_user_is_director()) {
        http_response_code(403);
        echo 'Access denied';
        exit;
    }
}
