<?php

require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/delay_notification_period.php';

return function () {
    $_SESSION = array();

    test_assert_same(
        get_journal_period(4, null, null, '2026-08-21'),
        get_delay_notification_period_from_session('2026-08-21'),
        'Delay notifications must default to the current quarter'
    );

    $_SESSION['delay_notification_period_mode'] = 7;
    $_SESSION['delay_notification_period_start_date'] = '2026-01-15';
    $_SESSION['delay_notification_period_stop_date'] = '2026-02-10';

    $manualPeriod = get_delay_notification_period_from_session('2026-08-21');
    test_assert_same('2026-01-15', $manualPeriod['start_date'], 'Delay notification manual period must retain its start date');
    test_assert_same('2026-02-10', $manualPeriod['stop_date'], 'Delay notification manual period must retain its stop date');

    $renderer = file_get_contents(__DIR__ . '/../inc/delay_notification_period.php');
    test_assert_true(
        strpos($renderer, 'delay_notification_period_type') !== false
            && strpos($renderer, 'Задать вручную') !== false,
        'Delay notification period filter must support the standard period selector and manual range'
    );

    $controller = file_get_contents(__DIR__ . '/../ajax/set_delay_notification_period.php');
    test_assert_true(
        strpos($controller, 'require_ajax_superuser') !== false
            && strpos($controller, 'delay_notification_period_mode') !== false,
        'Only supervisors may store the selected delay notification period'
    );
};
