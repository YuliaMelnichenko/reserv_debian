<?php

require_once __DIR__ . '/../../inc/authentication.php';
require_once __DIR__ . '/../../inc/password_hash_audit.php';

return function ($link) {
    $legacyHash = auth_legacy_password_hash('legacy-password');
    $modernHash = auth_create_password_hash('modern-password');

    foreach (array(
        array(611, 'legacy-active', $legacyHash, '', 1),
        array(612, 'modern-active', $legacyHash, $modernHash, 1),
        array(613, 'invalid-active', '', '', 1),
        array(614, 'legacy-archived', $legacyHash, '', 0),
    ) as $employee) {
        test_assert_same(
            true,
            db_execute(
                $link,
                'INSERT INTO employees (ID, LOGIN, PASSWD, PASSWORD_HASH, FIRSTNAME, LASTNAME, SURNAME, RELEVANCE) VALUES (?, ?, ?, ?, \'Тест\', \'Тестович\', \'Отчёт\', ?)',
                'isssi',
                $employee
            ),
            'Password hash audit fixture must be created'
        );
    }

    $summary = password_hash_audit_get_summary($link);
    test_assert_same(true, $summary['migration_applied'], 'Password hash audit requires migration 005');
    test_assert_same(3, $summary['active']['total'], 'All active employees must be counted');
    test_assert_same(1, $summary['active']['modern_hash'], 'Modern active hashes must be counted');
    test_assert_same(1, $summary['active']['legacy_md5'], 'Remaining active MD5 hashes must be counted');
    test_assert_same(1, $summary['active']['without_supported_hash'], 'Active records without a usable hash must be visible');
    test_assert_same(1, $summary['archived']['total'], 'Archived employees must be counted separately');
    test_assert_same(1, $summary['archived']['legacy_md5'], 'Archived MD5 hashes must be reported separately');
};
