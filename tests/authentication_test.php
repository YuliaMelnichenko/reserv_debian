<?php

require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/authentication.php';

return function () {
    test_assert_same('TORI_REMEMBER_TOKEN', auth_remember_token_cookie_name(), 'Remembered access must use a dedicated cookie');
    test_assert_same(60 * 60 * 24 * 30, auth_remember_token_lifetime_seconds(), 'Remembered access must expire after 30 days');
    test_assert_same(
        hash('sha256', 'token'),
        auth_remember_token_hash('token'),
        'Only a token hash may be stored in the database'
    );
    test_assert_true(auth_remember_token_is_valid_format(str_repeat('a', 64)), 'A 32-byte hexadecimal token must be accepted');
    test_assert_true(!auth_remember_token_is_valid_format('invalid'), 'Invalid remembered tokens must be rejected');

    $source = file_get_contents(__DIR__ . '/../inc/access.php');
    test_assert_true(
        strpos($source, 'auth_restore_session_from_remember_token') !== false,
        'Every access check must be able to restore an expired session'
    );
    $authenticationSource = file_get_contents(__DIR__ . '/../inc/authentication.php');
    test_assert_true(
        strpos($authenticationSource, 'auth_refresh_remember_token') !== false
            && strpos($authenticationSource, 'LAST_USED_DT = ?') !== false
            && strpos($authenticationSource, 'auth_issue_remember_token($link, (int) $employee[\'id\'])') === false,
        'Session restoration must extend the existing token instead of creating a new database row'
    );
};
