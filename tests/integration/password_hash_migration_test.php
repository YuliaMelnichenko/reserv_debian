<?php

require_once __DIR__ . '/../../inc/authentication.php';

return function ($link) {
    $legacyPassword = 'legacy-pass-123';
    $legacyHash = auth_legacy_password_hash($legacyPassword);
    $created = db_execute(
        $link,
        "INSERT INTO employees (ID, LOGIN, PASSWD, FIRSTNAME, LASTNAME, SURNAME)
         VALUES (601, 'legacy-user', ?, 'Тест', 'Тестович', 'Миграция')",
        's',
        array($legacyHash)
    );
    test_assert_same(true, $created, 'Legacy employee fixture must be created');

    $employeeResult = auth_find_employee_by_login($link, 'legacy-user');
    $employee = db_fetch_one($employeeResult);
    test_assert_true($employee !== null, 'Legacy employee must be found by login');

    $legacyVerification = auth_verify_employee_password($employee, $legacyPassword);
    test_assert_same(true, $legacyVerification['is_valid'], 'Legacy MD5 password must remain valid during migration');
    test_assert_same(true, $legacyVerification['uses_legacy_hash'], 'Legacy employee must be identified for upgrade');
    test_assert_same(true, auth_upgrade_employee_password_hash($link, 601, $legacyPassword), 'Successful legacy login must save a modern password hash');

    $upgraded = db_fetch_one(db_query($link, 'SELECT PASSWORD_HASH FROM employees WHERE ID = 601'));
    test_assert_true(password_verify($legacyPassword, $upgraded['PASSWORD_HASH']), 'Stored modern password hash must verify the original password');

    $employee = db_fetch_one(auth_find_employee_by_login($link, 'legacy-user'));
    $modernVerification = auth_verify_employee_password($employee, $legacyPassword);
    test_assert_same(true, $modernVerification['is_valid'], 'Upgraded employee must authenticate with the modern password hash');
    test_assert_same(false, $modernVerification['uses_legacy_hash'], 'Upgraded employee must no longer rely on MD5 authentication');
    test_assert_same(false, auth_verify_employee_password($employee, 'incorrect-password')['is_valid'], 'Incorrect password must be rejected');
};
