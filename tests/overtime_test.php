<?php

require_once __DIR__ . '/../inc/overtime.php';

return function () {
    test_assert_same(
        array('2026-07-01 00:00:00', '2026-07-20 23:59:59'),
        getPeriodBounds('quarter', '2026-07-20'),
        'The current quarter must begin on July 1'
    );
    test_assert_same(
        array('2026-07-20 00:00:00', '2026-07-20 23:59:59'),
        getPeriodBounds('week', '2026-07-20'),
        'A Monday weekly period must begin on the same day'
    );
    test_assert_same(
        array('2026-06-29 00:00:00', '2026-06-30 23:59:59'),
        getOvertimePeriodBounds('custom', '2026-06-29', '2026-06-30', '2026-07-20'),
        'A custom overtime period must preserve both dates'
    );

    test_assert_same(9.0, normalizeOvertimeThreshold(''), 'An empty threshold must use nine hours');
    test_assert_same(8.5, normalizeOvertimeThreshold('8.5'), 'A positive threshold must be preserved');
    test_assert_same(10.0, calculateOvertimeDayHours(8, 3, 1), 'Outside work must be added and pauses subtracted');
    test_assert_same(0.0, calculateOvertimeDayHours(1, 0, 2), 'A day duration must never become negative');
    test_assert_same('1 ч 30 мин', formatHours(1.5), 'Fractional hours must be formatted as hours and minutes');
    test_assert_same('—', formatHours(0), 'A zero duration must use the legacy empty marker');

    $exceptionThrown = false;
    try {
        getOvertimePeriodBounds('custom', '2026-07-10', '2026-07-01');
    }
    catch (InvalidArgumentException $e) {
        $exceptionThrown = true;
    }
    test_assert_same(true, $exceptionThrown, 'A reversed custom period must be rejected');
};
