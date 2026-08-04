<?php

require_once __DIR__ . '/../inc/offsite_work.php';

return function () {
    $intervals = build_offsite_work_daily_intervals(
        '2026-07-16',
        '2026-07-20',
        '09:00',
        '17:00'
    );

    test_assert_same(5, count($intervals), 'A five-day range must create exactly five intervals');
    test_assert_same(
        array('2026-07-16', '2026-07-17', '2026-07-18', '2026-07-19', '2026-07-20'),
        array_column($intervals, 'date'),
        'Offsite work dates must not escape the selected range'
    );
    test_assert_same(
        '2026-07-16 09:00:00',
        $intervals[0]['start'],
        'The first interval must use the selected start date'
    );
    test_assert_same(
        '2026-07-20 17:00:00',
        $intervals[4]['stop'],
        'The last interval must use the selected stop date'
    );

    $crossMonthIntervals = build_offsite_work_daily_intervals(
        '2026-07-30',
        '2026-08-02',
        '10:00',
        '12:00'
    );
    test_assert_same(
        array('2026-07-30', '2026-07-31', '2026-08-01', '2026-08-02'),
        array_column($crossMonthIntervals, 'date'),
        'A range crossing a month boundary must contain only its four selected dates'
    );

    $filtered = filter_offsite_work_intervals_by_dates(
        $intervals,
        array('2026-07-16', '2026-07-20', '2025-01-01')
    );
    test_assert_same(
        array('2026-07-16', '2026-07-20'),
        array_column($filtered, 'date'),
        'Filtering must never introduce dates that were not in the selected range'
    );

    $invalidThrown = false;

    try {
        build_offsite_work_daily_intervals('2026-07-20', '2026-07-16', '09:00', '17:00');
    } catch (InvalidArgumentException $error) {
        $invalidThrown = true;
    }

    test_assert_same(true, $invalidThrown, 'A reversed range must be rejected before database writes');
};
