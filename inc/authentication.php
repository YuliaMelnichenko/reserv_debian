<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/request.php';
require_once __DIR__ . '/session.php';

function auth_remember_token_cookie_name()
{
    return 'TORI_REMEMBER_TOKEN';
}

function auth_legacy_password_hash($password)
{
    return md5(md5(trim((string) $password)));
}

function auth_create_password_hash($password)
{
    return password_hash((string) $password, PASSWORD_DEFAULT);
}

function auth_password_hash_column_exists($link)
{
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    $result = db_query($link, "SHOW COLUMNS FROM employees LIKE 'PASSWORD_HASH'");

    if ($result === false) {
        throw new RuntimeException(db_error($link));
    }

    $exists = db_has_rows($result);
    return $exists;
}

function auth_find_employee_by_login($link, $login)
{
    $passwordHashField = auth_password_hash_column_exists($link)
        ? 'PASSWORD_HASH'
        : 'NULL AS PASSWORD_HASH';

    return db_query(
        $link,
        'SELECT id, rate, defaultStartTime, allowedDelayMinutes, userTimeZoneMins, dayTransitionTime, remoteWork, '
            . 'passwd, ' . $passwordHashField . ' FROM employees WHERE login = ? LIMIT 2',
        's',
        array((string) $login)
    );
}

function auth_verify_employee_password($employee, $password)
{
    $password = (string) $password;
    $modernHash = trim((string) ($employee['PASSWORD_HASH'] ?? ''));

    if ($modernHash !== '' && password_verify($password, $modernHash)) {
        return array(
            'is_valid' => true,
            'uses_legacy_hash' => false,
            'needs_hash_upgrade' => password_needs_rehash($modernHash, PASSWORD_DEFAULT),
        );
    }

    $legacyHash = (string) ($employee['passwd'] ?? '');

    if (hash_equals($legacyHash, auth_legacy_password_hash($password))) {
        return array(
            'is_valid' => true,
            'uses_legacy_hash' => true,
            'needs_hash_upgrade' => true,
        );
    }

    return array(
        'is_valid' => false,
        'uses_legacy_hash' => false,
        'needs_hash_upgrade' => false,
    );
}

function auth_upgrade_employee_password_hash($link, $employeeId, $password)
{
    if (!auth_password_hash_column_exists($link)) {
        return true;
    }

    return db_execute(
        $link,
        'UPDATE employees SET PASSWORD_HASH = ? WHERE id = ?',
        'si',
        array(auth_create_password_hash($password), (int) $employeeId)
    );
}

function auth_remember_token_lifetime_seconds()
{
    return 60 * 60 * 24 * 30;
}

function auth_remember_token_hash($token)
{
    return hash('sha256', (string) $token);
}

function auth_remember_token_is_valid_format($token)
{
    return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) === 1;
}

function auth_remember_token_cookie_options($expires)
{
    return app_cookie_options((int) $expires, '/', true, 'Lax');
}

function auth_clear_remember_token_cookie()
{
    $name = auth_remember_token_cookie_name();

    if (!isset($_COOKIE[$name])) {
        return;
    }

    setcookie($name, '', auth_remember_token_cookie_options(time() - 3600));
    unset($_COOKIE[$name]);
}

function auth_open_database()
{
    $env = @parse_ini_file(__DIR__ . '/../.env');

    if (!is_array($env) || !isset($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME'])) {
        return false;
    }

    $link = db_connect(
        $env['DB_HOST'],
        $env['DB_USER'],
        $env['DB_PASS'],
        $env['DB_NAME'],
        isset($env['DB_PORT']) ? (int) $env['DB_PORT'] : 3306
    );

    if (!$link) {
        return false;
    }

    db_set_charset($link, 'utf8');
    return $link;
}

function auth_employee_session_data($employee)
{
    $defaultStartTime = (string) $employee['defaultStartTime'];
    $allowedDelay = (int) $employee['allowedDelayMinutes'];
    $timezoneMinutes = (int) $employee['userTimeZoneMins'];
    $sign = $timezoneMinutes < 0 ? '-' : '+';
    $timezoneMinutes = abs($timezoneMinutes);

    return array(
        'ss_id' => (int) $employee['id'],
        'ss_rate' => $employee['rate'],
        'ss_defaultStartTime' => $defaultStartTime,
        'ss_defaultStartTimeWithDelay' => date('H:i:s', strtotime($defaultStartTime . ' + ' . $allowedDelay . ' minute')),
        'ss_defaultStartHour' => (int) date('H', strtotime($defaultStartTime)),
        'ss_defaultStartMinute' => (int) date('i', strtotime($defaultStartTime)),
        'ss_allowedDelay' => $allowedDelay,
        'ss_mode' => 1,
        'ss_delay_show_save' => 0,
        'ss_UserTimeZoneMins' => (int) $employee['userTimeZoneMins'],
        'ss_UserTimeZoneStr' => sprintf('UTC%s%02d:%02d', $sign, intdiv($timezoneMinutes, 60), $timezoneMinutes % 60),
        'ss_dayTransitionTime' => '00:00:00',
        'ss_RemoteWork' => (int) $employee['remoteWork'] === 1 ? 1 : 0,
        'ss_RemoteWorkStr' => (int) $employee['remoteWork'] === 1 ? 'УДАЛЕННЫЙ' : 'В ОФИСЕ',
        'ss_visiting_ID' => -1,
        'rep_start_stop_date_mode' => 2,
        'ss_dayWasChanged' => 0,
    );
}

function auth_start_employee_session($employee)
{
    $_SESSION = array();
    session_regenerate_id(true);

    foreach (auth_employee_session_data($employee) as $key => $value) {
        $_SESSION[$key] = $value;
    }

    $_SESSION['ss_defaultStartTimeWithDelayVal'] = strtotime($_SESSION['ss_defaultStartTimeWithDelay']);
    $_SESSION['ss_sessid'] = session_id();
}

function auth_remove_expired_remember_tokens($link)
{
    return db_execute(
        $link,
        'DELETE FROM auth_remember_tokens WHERE EXPIRES_AT < ?',
        's',
        array(date('Y-m-d H:i:s'))
    );
}

function auth_refresh_remember_token($link, $token)
{
    if (!auth_remember_token_is_valid_format($token)) {
        return false;
    }

    $expires = time() + auth_remember_token_lifetime_seconds();
    $now = date('Y-m-d H:i:s');
    $refreshed = db_execute(
        $link,
        'UPDATE auth_remember_tokens SET EXPIRES_AT = ?, LAST_USED_DT = ? WHERE TOKEN_HASH = ?',
        'sss',
        array(
            date('Y-m-d H:i:s', $expires),
            $now,
            auth_remember_token_hash($token),
        )
    );

    if (!$refreshed || !setcookie(auth_remember_token_cookie_name(), $token, auth_remember_token_cookie_options($expires))) {
        return false;
    }

    $_COOKIE[auth_remember_token_cookie_name()] = $token;
    return true;
}

function auth_issue_remember_token($link, $userId)
{
    $name = auth_remember_token_cookie_name();
    $currentToken = request_cookie_string($name);

    auth_remove_expired_remember_tokens($link);

    if (auth_remember_token_is_valid_format($currentToken)) {
        db_execute(
            $link,
            'DELETE FROM auth_remember_tokens WHERE TOKEN_HASH = ?',
            's',
            array(auth_remember_token_hash($currentToken))
        );
    }

    try {
        $token = bin2hex(random_bytes(32));
    } catch (Throwable $error) {
        return false;
    }

    $now = date('Y-m-d H:i:s');
    $expires = time() + auth_remember_token_lifetime_seconds();
    $expiresAt = date('Y-m-d H:i:s', $expires);
    $saved = db_execute(
        $link,
        'INSERT INTO auth_remember_tokens (USERID, TOKEN_HASH, EXPIRES_AT, CREATED_DT, LAST_USED_DT) VALUES (?, ?, ?, ?, ?)',
        'issss',
        array((int) $userId, auth_remember_token_hash($token), $expiresAt, $now, $now)
    );

    if (!$saved) {
        return false;
    }

    if (!setcookie($name, $token, auth_remember_token_cookie_options($expires))) {
        db_execute(
            $link,
            'DELETE FROM auth_remember_tokens WHERE TOKEN_HASH = ?',
            's',
            array(auth_remember_token_hash($token))
        );
        return false;
    }

    $_COOKIE[$name] = $token;
    return true;
}

function auth_revoke_remember_token($link)
{
    $token = request_cookie_string(auth_remember_token_cookie_name());

    if (auth_remember_token_is_valid_format($token) && $link) {
        db_execute(
            $link,
            'DELETE FROM auth_remember_tokens WHERE TOKEN_HASH = ?',
            's',
            array(auth_remember_token_hash($token))
        );
    }

    auth_clear_remember_token_cookie();
}

function auth_restore_session_from_remember_token()
{
    $token = request_cookie_string(auth_remember_token_cookie_name());

    if (!auth_remember_token_is_valid_format($token)) {
        if ($token !== '') {
            auth_clear_remember_token_cookie();
        }
        return false;
    }

    $link = auth_open_database();

    if (!$link) {
        return false;
    }

    auth_remove_expired_remember_tokens($link);
    $query = db_query(
        $link,
        'SELECT e.id, e.rate, e.defaultStartTime, e.allowedDelayMinutes, e.userTimeZoneMins, e.remoteWork
         FROM auth_remember_tokens t
         INNER JOIN employees e ON e.id = t.USERID
         WHERE t.TOKEN_HASH = ? AND t.EXPIRES_AT >= ?
         LIMIT 1',
        'ss',
        array(auth_remember_token_hash($token), date('Y-m-d H:i:s'))
    );
    $employee = db_fetch_one($query);

    if (!$employee) {
        db_close($link);
        auth_clear_remember_token_cookie();
        return false;
    }

    auth_start_employee_session($employee);
    $refreshed = auth_refresh_remember_token($link, $token);
    db_close($link);

    if (!$refreshed) {
        auth_clear_remember_token_cookie();
    }

    if (function_exists('csrf_rotate_token')) {
        csrf_rotate_token();
    }

    return true;
}
