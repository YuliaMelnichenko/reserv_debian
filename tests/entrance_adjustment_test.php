<?php

require_once __DIR__ . '/../inc/entrance_adjustment.php';

return function () {
    $baseVisit = array(
        'in_dt' => '2026-07-21 08:00:00',
        'out_dt' => '0000-00-00 00:00:00',
        'eat_start_dt' => '0000-00-00 00:00:00',
        'eat_stop_dt' => '0000-00-00 00:00:00',
        'state' => 2,
    );

    $adjustment = build_entrance_adjustment($baseVisit, '09:15');
    test_assert_same(3, $adjustment['code'], 'An active day before lunch must keep response code 3');
    test_assert_same('2026-07-21 09:15:00', $adjustment['in_dt'], 'Only the arrival time must change');

    $closedVisit = array(
        'in_dt' => '2026-07-21 08:00:00',
        'out_dt' => '2026-07-21 17:00:00',
        'eat_start_dt' => '2026-07-21 12:00:00',
        'eat_stop_dt' => '2026-07-21 13:00:00',
        'state' => 0,
    );

    $adjustment = build_entrance_adjustment($closedVisit, '12:30:00');
    test_assert_same(1, $adjustment['code'], 'A closed day must keep response code 1');
    test_assert_same('2026-07-21 12:30:01', $adjustment['eat_start_dt'], 'Lunch must move after a late adjusted arrival');
    test_assert_same('2026-07-21 13:30:01', $adjustment['eat_stop_dt'], 'Lunch duration must be preserved');
    test_assert_same('2026-07-21 17:00:00', $adjustment['out_dt'], 'Departure must stay unchanged when it remains valid');

    $adjustment = build_entrance_adjustment($closedVisit, '16:30:00');
    test_assert_same('2026-07-21 17:30:01', $adjustment['eat_stop_dt'], 'Shifted lunch may cross the old departure');
    test_assert_same('2026-07-21 17:30:02', $adjustment['out_dt'], 'Departure must move after shifted lunch');

    test_assert_same(
        -10,
        build_entrance_adjustment($closedVisit, '18:00:00')['code'],
        'Arrival after departure must keep response code -10'
    );

    $afterLunchVisit = $closedVisit;
    $afterLunchVisit['state'] = 4;
    test_assert_same(
        -11,
        build_entrance_adjustment($afterLunchVisit, '12:30:00')['code'],
        'Arrival after lunch start must keep response code -11 for state 4'
    );

    $lunchVisit = $closedVisit;
    $lunchVisit['state'] = 3;
    test_assert_same(
        -12,
        build_entrance_adjustment($lunchVisit, '12:00:00')['code'],
        'Arrival at lunch start must keep response code -12 for state 3'
    );

    $flowFiles = array(
        __DIR__ . '/../ajax/adj_in_time.php',
        __DIR__ . '/../ajax/delete_user_visitiong_info_by_currentDay.php',
    );

    foreach ($flowFiles as $file) {
        $source = file_get_contents($file);
        test_assert_same(
            0,
            preg_match('/\b(?:in_time|out_time|eat_start|eat_stop)\b/', $source),
            'Entrance adjustment flow must use DATETIME columns in ' . basename($file)
        );
    }

    $functionsSource = file_get_contents(__DIR__ . '/../funcs.php');
    $functionStart = strpos($functionsSource, 'function get_users_current_day_in_time_by_superuser');
    $functionStop = strpos($functionsSource, 'function get_penalties', $functionStart);
    $listFunction = substr($functionsSource, $functionStart, $functionStop - $functionStart);

    test_assert_same(
        0,
        preg_match('/\b(?:in_time|out_time|eat_start|eat_stop)\b/', $listFunction),
        'The supervisor entrance list must use DATETIME columns'
    );
};
