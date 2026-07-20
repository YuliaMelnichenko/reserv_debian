<?php

require_once __DIR__ . '/../inc/workday_period.php';

return function () {
    test_assert_same(
        '00:00:00',
        get_standard_day_transition_time(),
        'The standard workday must start at midnight'
    );
    test_assert_same(
        '00:00:00',
        normalize_day_transition_time('03:00:00'),
        'Legacy employee transition times must be normalized'
    );

    $expected = array('2026-07-20 00:00:00', '2026-07-20 23:59:59');

    test_assert_same(
        $expected,
        datetimestr_to_day_start_stop_DT_ex_str('2026-07-20 05:00:00', '03:00:00'),
        'An early arrival must remain in the current calendar day'
    );
    test_assert_same(
        $expected,
        datetimestr_to_day_start_stop_DT_ex_str('2026-07-20 18:30:00', 'NDF'),
        'An undefined transition must use the standard period'
    );
    test_assert_same(
        $expected,
        datetimestr_to_day_start_stop_DT_ex_str_idx('2026-07-20 05:00:00', '03:00:00'),
        'The compatibility function must return the same period'
    );
    test_assert_same(
        array('2024-02-29 00:00:00', '2024-02-29 23:59:59'),
        datetimestr_to_day_start_stop_DT_ex_str('2024-02-29 12:00:00', '00:00:00'),
        'Leap-day boundaries must be calculated correctly'
    );
};
