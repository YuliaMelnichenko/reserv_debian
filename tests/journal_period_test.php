<?php

require_once __DIR__ . '/../inc/journal_period.php';

return function () {
    foreach (array(
        array(1, '2026-10-05', '2026-10-07'),
        array(2, '2026-10-01', '2026-10-07'),
        array(3, '2026-09-01', '2026-09-30'),
        array(4, '2026-10-01', '2026-10-07'),
        array(5, '2026-07-01', '2026-09-30'),
    ) as $case) {
        $period = get_journal_period($case[0], null, null, '2026-10-07');
        test_assert_same($case[1], $period['start_date'], 'Preset start must be correct');
        test_assert_same($case[2], $period['stop_date'], 'Preset end must be correct');
    }
    $period = get_journal_period(5, null, null, '2026-01-02');
    test_assert_same('2025-10-01', $period['start_date'], 'Previous quarter must cross the year boundary');
    test_assert_same('2026-01-01', $period['stop_exclusive'], 'Exclusive end must cross the year boundary');
    $period = get_journal_period(7, '2024-02-29', '2024-02-29', '2026-10-07');
    test_assert_same('2024-03-01', $period['stop_exclusive'], 'A single leap day must be selectable');
    foreach (array(
        array(null, '2026-10-07'),
        array('2026-02-30', '2026-03-01'),
        array('invalid', '2026-10-07'),
        array('2026-10-08', '2026-10-07'),
        array('2024-01-01', '2026-10-07'),
    ) as $case) {
        test_assert_same(null, get_journal_period(7, $case[0], $case[1], '2026-10-07'), 'Invalid manual ranges must be rejected');
    }
    test_assert_same(null, get_journal_period(6), 'Unsupported modes must be rejected');

    $savedSession = isset($_SESSION) ? $_SESSION : array();
    try {
        $_SESSION = array();
        test_assert_same(4, get_journal_period_from_session('delay_journal_period', '2026-10-07')['mode'], 'Default must be the current quarter');
        $_SESSION['delay_notification_period_mode'] = 7;
        $_SESSION['delay_notification_period_start_date'] = '2026-09-01';
        $_SESSION['delay_notification_period_stop_date'] = '2026-09-30';
        test_assert_same('2026-09-01', get_journal_period_from_session('delay_notification_period', '2026-10-07')['start_date'], 'Supervisor selection must persist');
        test_assert_same('2026-10-01', get_journal_period_from_session('delay_journal_period', '2026-10-07')['start_date'], 'Personal selection must remain independent');
        $_SESSION['delay_notification_period_start_date'] = 'invalid';
        test_assert_same(4, get_journal_period_from_session('delay_notification_period', '2026-10-07')['mode'], 'Invalid saved dates must fall back to the default');
    } finally {
        $_SESSION = $savedSession;
    }
};
