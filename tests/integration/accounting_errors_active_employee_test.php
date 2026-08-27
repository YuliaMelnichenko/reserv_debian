<?php

require_once __DIR__ . '/../../inc/accounting_errors.php';

return function ($link) {
    list($periodStart, $periodStop) = accounting_errors_get_range();
    $inactiveUserId = 701;
    $supervisorId = 702;

    integration_seed_employee($link, $inactiveUserId, 'Неактуальный');
    integration_seed_employee($link, $supervisorId, 'Руководитель');

    test_assert_same(
        true,
        db_execute($link, 'UPDATE employees SET RELEVANCE = 0 WHERE ID = ?', 'i', array($inactiveUserId)),
        'Inactive employee fixture must be marked as not relevant'
    );
    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO `GROUPS` (USERID, SUPERVISORID, TYPE) VALUES (?, ?, ?)',
            'iis',
            array($inactiveUserId, $supervisorId, '3')
        ),
        'Inactive employee supervisor membership fixture must be created'
    );
    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO accounting_errors (USERID, ERROR_DATE, STATUS, COMMENT, CREATED_DT) VALUES (?, ?, 0, ?, NOW())',
            'iss',
            array($inactiveUserId, $periodStart, '')
        ),
        'Inactive employee accounting-error fixture must be created'
    );
    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO business_trip_missing_data (USERID, TRIP_DATE, CREATED_DT) VALUES (?, ?, NOW())',
            'is',
            array($inactiveUserId, $periodStart)
        ),
        'Inactive employee business-trip fixture must be created'
    );

    test_assert_same(
        true,
        db_execute($link, "DELETE FROM schema_migrations WHERE VERSION = '006'"),
        'Inactive-record cleanup migration must be made pending for verification'
    );
    test_assert_same(
        true,
        migrations_apply_pending($link, migration_catalog(__DIR__ . '/../../sql/migrations')) !== false,
        'Inactive-record cleanup migration must be applied successfully'
    );

    $remainingError = db_fetch_one(db_query(
        $link,
        'SELECT COUNT(*) AS CNT FROM accounting_errors WHERE USERID = ?',
        'i',
        array($inactiveUserId)
    ));
    $remainingTripReminder = db_fetch_one(db_query(
        $link,
        'SELECT COUNT(*) AS CNT FROM business_trip_missing_data WHERE USERID = ?',
        'i',
        array($inactiveUserId)
    ));
    test_assert_same(0, (int)$remainingError['CNT'], 'Migration must delete inactive employee accounting errors');
    test_assert_same(0, (int)$remainingTripReminder['CNT'], 'Migration must delete inactive employee business-trip reminders');

    test_assert_same(0, sync_accounting_errors_for_user($link, $inactiveUserId), 'Inactive employees must not receive new accounting errors');
    test_assert_same(0, get_accounting_errors_count($link, $inactiveUserId), 'Inactive employee accounting errors must be hidden');
    test_assert_same(
        array(),
        get_business_trip_missing_data_rows($link, $inactiveUserId),
        'Inactive employee business-trip reminders must be hidden'
    );
    test_assert_same(
        array(),
        get_accounting_errors_supervised_users($link, $supervisorId),
        'Inactive employees must be omitted from supervisor accounting notifications'
    );
};
