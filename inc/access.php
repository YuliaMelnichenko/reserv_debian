<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/output.php';

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

function access_request_is_https()
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

function csrf_set_cookie($token)
{
    setcookie('TORI_CSRF_TOKEN', $token, array(
        'expires' => 0,
        'path' => '/',
        'secure' => access_request_is_https(),
        'httponly' => false,
        'samesite' => 'Strict',
    ));

    $_COOKIE['TORI_CSRF_TOKEN'] = $token;
}

function csrf_ensure_token()
{
    if (
        empty($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
        || strlen($_SESSION['csrf_token']) < 64
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $token = $_SESSION['csrf_token'];
    $cookieToken = isset($_COOKIE['TORI_CSRF_TOKEN'])
        ? (string) $_COOKIE['TORI_CSRF_TOKEN']
        : '';

    if ($cookieToken === '' || !hash_equals($token, $cookieToken)) {
        csrf_set_cookie($token);
    }

    return $token;
}

function csrf_rotate_token()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    csrf_set_cookie($_SESSION['csrf_token']);

    return $_SESSION['csrf_token'];
}

function csrf_request_is_unsafe()
{
    $method = strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
    return !in_array($method, array('GET', 'HEAD', 'OPTIONS'), true);
}

function require_csrf_for_unsafe_request($ajaxRequest = false)
{
    if (!csrf_request_is_unsafe()) {
        csrf_ensure_token();
        return;
    }

    $expectedToken = csrf_ensure_token();
    $providedToken = isset($_SERVER['HTTP_X_CSRF_TOKEN'])
        ? (string) $_SERVER['HTTP_X_CSRF_TOKEN']
        : (isset($_POST['_csrf']) ? (string) $_POST['_csrf'] : '');

    if ($providedToken !== '' && hash_equals($expectedToken, $providedToken)) {
        return;
    }

    if ($ajaxRequest) {
        deny_ajax_access(403, 'CSRF_TOKEN_INVALID');
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo 'Invalid request token';
    exit;
}

function require_ajax_auth()
{
    if (!access_session_is_valid()) {
        deny_ajax_access(401, 'AUTH_REQUIRED');
    }

    require_csrf_for_unsafe_request(true);
}

function access_current_user_is_director()
{
    return isset($_SESSION['ss_id'])
        && in_array((int) $_SESSION['ss_id'], array(500, 501), true);
}

function access_open_database()
{
    $env = parse_ini_file(__DIR__ . '/../.env');

    if (!is_array($env)) {
        return false;
    }

    $required = array('DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME');

    foreach ($required as $key) {
        if (!array_key_exists($key, $env)) {
            return false;
        }
    }

    $link = mysqli_connect(
        $env['DB_HOST'],
        $env['DB_USER'],
        $env['DB_PASS'],
        $env['DB_NAME']
    );

    if (!$link) {
        return false;
    }

    mysqli_set_charset($link, 'utf8');
    return $link;
}

function access_current_user_is_superuser(){
    if (access_current_user_is_director()) {
        return true;
    }

    $link = access_open_database();

    if (!$link) {
        return false;
    }

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

function require_ajax_add_time_access($recordID)
{
    require_ajax_auth();

    $link = access_open_database();

    if (!$link) {
        deny_ajax_access(500, 'DATABASE_ERROR');
    }

    $result = db_query(
        $link,
        'SELECT USERID FROM ADD_TIME WHERE ID = ? LIMIT 1',
        'i',
        array((int) $recordID)
    );
    $row = $result ? mysqli_fetch_assoc($result) : false;
    mysqli_close($link);

    if (!$row) {
        deny_ajax_access(404, 'NOT_FOUND');
    }

    require_ajax_self_or_superuser((int) $row['USERID']);
}

function require_page_auth(){
    if (!access_session_is_valid()) {
        header('Location: auth.php');
        exit;
    }

    require_csrf_for_unsafe_request(false);
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
