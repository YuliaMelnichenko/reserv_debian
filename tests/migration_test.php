<?php

require_once __DIR__ . '/../inc/migrations.php';

return function () {
    $catalog = migration_catalog(__DIR__ . '/../sql/migrations');

    test_assert_same(
        array('001', '002', '003'),
        array_column($catalog, 'id'),
        'Schema migrations must have stable versions'
    );

    foreach ($catalog as $migration) {
        test_assert_true(
            preg_match('/^[a-f0-9]{64}$/', $migration['checksum']) === 1,
            'Every migration must have a SHA-256 checksum: ' . $migration['file_name']
        );
    }

    $migrationScript = file_get_contents(__DIR__ . '/../scripts/db/migrate.php');
    test_assert_true(
        strpos($migrationScript, '--confirm=') !== false,
        'The migration script must require an explicit database confirmation'
    );
    test_assert_true(
        strpos($migrationScript, 'TORI_MIGRATION_DB_HOST') !== false,
        'The migration script must require explicit database environment settings'
    );

    $smokeScript = file_get_contents(__DIR__ . '/../scripts/smoke-stage.sh');
    test_assert_true(
        strpos($smokeScript, 'X-Tori-Health-Token') !== false
            && strpos($smokeScript, '/auth.php') !== false,
        'Stage smoke checks must verify protected health and authentication endpoints'
    );
};
