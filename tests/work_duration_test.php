<?php

require_once __DIR__ . '/../inc/work_duration.php';

return function () {
    test_assert_same(0, is_time_defined('00:00:00'), 'A zero time must be undefined');
    test_assert_same(0, is_time_defined('0000-00-00 00:00:00'), 'A zero datetime must be undefined');
    test_assert_same(1, is_time_defined('2026-07-20 09:00:00'), 'A real datetime must be defined');

    test_assert_same(
        24 * 3600,
        get_defined_time_range_duration('2026-06-29 11:00:00', '2026-06-30 11:00:00'),
        'A range crossing midnight must retain all hours'
    );
    test_assert_same(
        0,
        get_defined_time_range_duration('2026-06-30 11:00:00', '2026-06-29 11:00:00'),
        'A reversed range must not produce a negative duration'
    );
    test_assert_same(
        0,
        get_defined_time_range_duration('0000-00-00 00:00:00', '2026-06-29 11:00:00'),
        'A damaged range must not produce hundreds of hours'
    );

    test_assert_same(
        9 * 3600,
        get_work_time_duration_by_times_ex(
            '2026-07-20 09:00:00',
            '2026-07-20 18:00:00',
            '2026-07-20 13:00:00',
            '2026-07-20 14:00:00',
            0,
            0
        ),
        'A closed workday must use its recorded bounds'
    );
    test_assert_same(
        3 * 3600,
        get_work_time_duration_by_times_ex(
            '2026-07-20 09:00:00',
            '0000-00-00 00:00:00',
            '0000-00-00 00:00:00',
            '0000-00-00 00:00:00',
            2,
            1,
            '2026-07-20 12:00:00'
        ),
        'An open current workday must use the supplied current time'
    );
    test_assert_same(
        3600,
        get_eat_time_duration_by_times_ex(
            '2026-07-20 13:00:00',
            '2026-07-20 14:00:00',
            4,
            1
        ),
        'A completed lunch must use its recorded bounds'
    );

    $addTimeInfo = array(
        array('2026-07-20 07:00:00', '2026-07-20 09:00:00', 1, '', 1, 0, 7200, 0),
        array('2026-07-20 18:00:00', '2026-07-20 19:00:00', 1, '', -1, 0, 3600, 0),
        array('2026-07-20 11:00:00', '2026-07-20 11:30:00', 1, '', 0, 0, 1800, 1),
        array('0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, '', 0, 0, 0, 1),
    );

    test_assert_same(7200, get_add_time_duration_by_times_ex($addTimeInfo), 'Active offsite work must be added');
    test_assert_same(1800, get_pause_time_duration_by_times($addTimeInfo), 'Only valid active pauses must be subtracted');

    $durations = get_durations(
        '2026-07-20 09:00:00',
        '2026-07-20 18:00:00',
        '2026-07-20 13:00:00',
        '2026-07-20 14:00:00',
        $addTimeInfo,
        0,
        0,
        '2026-07-20 18:00:00',
        '09:00:00',
        30
    );

    test_assert_same(9 * 3600, $durations[0], 'The total work interval must be preserved');
    test_assert_same(3600, $durations[1], 'Lunch must be reported separately');
    test_assert_same(7200, $durations[2], 'Offsite work must be reported separately');
    test_assert_same(1800, $durations[5], 'Pauses must be reported separately');
    test_assert_same(34200, $durations[3], 'The net duration must combine work, lunch, offsite work and pauses');
};
