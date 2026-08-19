<?php

require_once __DIR__ . '/../../inc/accounting_errors.php';

return function ($link) {
    list($periodStart, $periodStop) = accounting_errors_get_range();

    if ($periodStop < $periodStart) {
        return;
    }

    $tripStart = $periodStart;
    $tripStop = min(
        $periodStop,
        date('Y-m-d', strtotime($tripStart . ' +1 day'))
    );
    integration_seed_employee($link, 301, 'Командировка');

    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO staff_leaves (user_id, fio, start_date, stop_date, event) VALUES (?, ?, ?, ?, ?)',
            'issss',
            array(301, 'Командировка Тест', $tripStart, $tripStop, 'Командировка')
        ),
        'Business trip fixture must be created'
    );

    $missingCount = sync_business_trip_missing_data_for_user($link, 301);
    $expectedDates = get_days_range_inclusive($tripStart, $tripStop);
    test_assert_same(
        count($expectedDates),
        $missingCount,
        'Every business-trip day without offsite work must be flagged'
    );
    test_assert_same(
        count($expectedDates),
        get_business_trip_missing_data_count($link, 301),
        'Missing business-trip days must be visible to the employee'
    );

    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO ADD_TIME (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'siississii',
            array(
                $tripStart,
                301,
                301,
                $tripStart . ' 09:00:00',
                $tripStop . ' 18:00:00',
                1,
                'Командировка',
                '',
                1,
                0,
            )
        ),
        'Approved offsite work fixture must be created'
    );

    sync_business_trip_missing_data_for_user($link, 301);
    test_assert_same(
        0,
        get_business_trip_missing_data_count($link, 301),
        'Completed offsite work must clear business-trip reminders'
    );
};
