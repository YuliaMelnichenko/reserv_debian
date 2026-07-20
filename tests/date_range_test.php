<?php

require_once __DIR__ . '/../inc/date_range.php';

return function () {
    test_assert_same(
        '2026-06-29 11:00:00',
        normalize_datetime_value('2026-06-29T11:00'),
        'A browser datetime value must be normalized for MySQL'
    );
    test_assert_same(null, normalize_datetime_value('2026-02-30 11:00:00'), 'An impossible date must be rejected');
    test_assert_same(null, normalize_date_value('2026-02-30'), 'An impossible calendar date must be rejected');
    test_assert_same(
        null,
        get_valid_datetime_range('2026-06-30 11:00:00', '2026-06-29 11:00:00'),
        'A reversed range must be rejected'
    );

    $segments = split_datetime_range_by_day('2026-06-29 11:00:00', '2026-06-30 11:00:00');
    test_assert_same(2, count($segments), 'A range crossing midnight must create two daily segments');
    test_assert_same('2026-06-29', $segments[0]['date'], 'The first segment must remain on June 29');
    test_assert_same(13 * 3600, $segments[0]['duration'], 'June 29 must contain thirteen hours');
    test_assert_same('2026-06-30', $segments[1]['date'], 'The second segment must continue on June 30');
    test_assert_same(11 * 3600, $segments[1]['duration'], 'June 30 must contain eleven hours');
    test_assert_same(
        24 * 3600,
        array_sum(array_column($segments, 'duration')),
        'Daily segments must preserve the full source duration'
    );

    $clipped = clip_datetime_range(
        '2026-06-29 11:00:00',
        '2026-06-30 11:00:00',
        '2026-06-30 00:00:00',
        '2026-07-01 00:00:00'
    );
    test_assert_same('2026-06-30 00:00:00', $clipped['start'], 'The range must be clipped at midnight');
    test_assert_same(11 * 3600, $clipped['duration'], 'The clipped day must retain its eleven hours');

    test_assert_same(
        array('2024-02-28', '2024-02-29', '2024-03-01'),
        get_days_range_inclusive('2024-02-28', '2024-03-01'),
        'Inclusive date ranges must support leap days'
    );
};
