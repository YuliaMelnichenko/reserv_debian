<?php

require_once __DIR__ . '/../inc/time_format.php';

return function () {
    test_assert_same('00:00:00', format_time_(0), 'Zero duration must be formatted');
    test_assert_same('23:59:59', format_time_(86399), 'A duration below one day must not include days');
    test_assert_same('1д 00:00:00', format_time_(86400), 'A full day must include the day count');
    test_assert_same('1д 01:01:01', format_time_(90061), 'A multi-day duration must preserve its remainder');
    test_assert_same('00:00:00', format_time_(-1), 'Negative list durations must remain clamped to zero');

    test_assert_same('00:59', format_time_d_hhmm_pure(3569), 'Seconds below 30 must round down');
    test_assert_same('01:00', format_time_d_hhmm_pure(3570), 'Minute rounding must carry into the next hour');
    test_assert_same('25:01:01', format_time_d_hhmmss_pure(90061), 'Report durations may exceed 24 hours');
    test_assert_same('ERR (time<0)', format_time_d_hhmmss_pure(-1), 'Negative durations must be explicit');
    test_assert_same('ERR (time<0)', format_time_d_hhmmss_pure_partial(-1), 'Negative decimal durations must be explicit');

    test_assert_same(0, time_defined('00:00:00'), 'A zero database time must be undefined');
    test_assert_same(1, time_defined('08:00:00'), 'A non-zero database time must be defined');
    test_assert_same(120, round_to_minute(91), 'More than 30 seconds must round up');
    test_assert_same(60, round_to_minute(90), 'Exactly 30 seconds must retain legacy rounding');
};
