<?php

return function () {
    $service = file_get_contents(__DIR__ . '/../inc/password_hash_audit.php');
    $script = file_get_contents(__DIR__ . '/../scripts/db/password-hash-report.php');

    test_assert_true(
        strpos($service, 'PASSWORD_HASH') !== false
            && strpos($service, "passwd REGEXP '^[0-9A-Fa-f]{32}$'") !== false,
        'The password hash audit must distinguish modern hashes from remaining MD5 hashes'
    );
    test_assert_same(0, preg_match('/\b(?:INSERT|UPDATE|DELETE|ALTER|DROP)\b/i', $service), 'The password hash audit service must be read-only');
    test_assert_true(
        strpos($script, 'TORI_PASSWORD_AUDIT_DB_HOST') !== false
            && strpos($script, 'This report is read-only') !== false,
        'The password hash report must use explicit audit settings and state its read-only behavior'
    );
};
