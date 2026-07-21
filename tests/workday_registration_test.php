<?php

require_once __DIR__ . '/../inc/workday_registration.php';

return function () {
    $periodStart = '2026-07-21 06:00:00';
    $periodStop = '2026-07-22 06:00:00';
    $now = '2026-07-21 08:00:00';
    $maxOpenShiftSeconds = 3 * 60 * 60;

    test_assert_same(
        true,
        is_workday_visit_current(
            array('in_dt' => '2026-07-21 07:00:00', 'state' => 0),
            $periodStart,
            $periodStop,
            $now,
            $maxOpenShiftSeconds
        ),
        'A visit inside the current reporting period must remain available'
    );

    test_assert_same(
        true,
        is_workday_visit_current(
            array('in_dt' => '2026-07-21 05:30:00', 'state' => 3),
            $periodStart,
            $periodStop,
            $now,
            $maxOpenShiftSeconds
        ),
        'A recent open overnight shift must remain available'
    );

    test_assert_same(
        false,
        is_workday_visit_current(
            array('in_dt' => '2026-07-21 04:00:00', 'state' => 3),
            $periodStart,
            $periodStop,
            $now,
            $maxOpenShiftSeconds
        ),
        'An open shift older than the allowed overlap must be rejected'
    );

    test_assert_same(
        false,
        is_workday_visit_current(
            array('in_dt' => '2026-07-21 05:30:00', 'state' => 0),
            $periodStart,
            $periodStop,
            $now,
            $maxOpenShiftSeconds
        ),
        'A closed shift outside the reporting period must be rejected'
    );

    test_assert_same(
        false,
        is_workday_visit_current(
            array('in_dt' => '2026-07-21 09:00:00', 'state' => 2),
            $periodStart,
            $periodStop,
            $now,
            $maxOpenShiftSeconds
        ),
        'A visit starting in the future must be rejected'
    );
};
