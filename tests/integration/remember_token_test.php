<?php

require_once __DIR__ . '/../../inc/authentication.php';

return function ($link) {
    $cookieName = auth_remember_token_cookie_name();
    $previousCookie = isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : null;
    $_COOKIE[$cookieName] = str_repeat('a', 64);

    try {
        test_assert_same(true, auth_issue_remember_token($link, 401), 'Remember token must be issued');
        $token = $_COOKIE[$cookieName];
        $storedToken = db_fetch_one(db_query(
            $link,
            'SELECT USERID, TOKEN_HASH, EXPIRES_AT, LAST_USED_DT FROM auth_remember_tokens WHERE USERID = ?',
            'i',
            array(401)
        ));

        test_assert_same(401, (int)$storedToken['USERID'], 'Remember token must belong to the issued employee');
        test_assert_same(auth_remember_token_hash($token), $storedToken['TOKEN_HASH'], 'Only token hash may be stored');
        test_assert_true($storedToken['EXPIRES_AT'] > date('Y-m-d H:i:s'), 'Remember token must have an expiry date');
        test_assert_same(true, auth_refresh_remember_token($link, $token), 'Remember token must be refreshed');

        auth_revoke_remember_token($link);
        $remaining = db_fetch_one(db_query(
            $link,
            'SELECT ID FROM auth_remember_tokens WHERE USERID = ?',
            'i',
            array(401)
        ));
        test_assert_same(null, $remaining, 'Revoked remember token must be deleted');
    } finally {
        if ($previousCookie === null) {
            unset($_COOKIE[$cookieName]);
        } else {
            $_COOKIE[$cookieName] = $previousCookie;
        }
    }
};
