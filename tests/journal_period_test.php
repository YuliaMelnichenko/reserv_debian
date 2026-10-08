<?php

require_once __DIR__ . '/../inc/journal_period.php';
require_once __DIR__ . '/../inc/journal_period_filter.php';

return function () {
    foreach (array(
        array(1, '2026-10-05', '2026-10-07'),
        array(2, '2026-10-01', '2026-10-07'),
        array(3, '2026-09-01', '2026-09-30'),
        array(4, '2026-10-01', '2026-10-07'),
        array(5, '2026-07-01', '2026-09-30'),
        array(6, '2026-07-01', '2026-10-07'),
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
    test_assert_same(null, get_journal_period(8), 'Unsupported modes must be rejected');

    $supervisorPage = file_get_contents(__DIR__ . '/../delay_approvement.php');
    $detailPage = file_get_contents(__DIR__ . '/../delay_approvement_user.php');
    $detailAjax = file_get_contents(__DIR__ . '/../ajax/get_delays_by_user.php');
    test_assert_true(
        strpos($supervisorPage, "load_supervisor_journal_period('delay_notification_period'") !== false
            && strpos($detailPage, "get_journal_period_from_session('delay_notification_period'") !== false
            && strpos($detailAjax, "get_journal_period_from_session('delay_notification_period'") !== false,
        'Supervisor list and both detail views must share the selected period'
    );
    $personalTable = file_get_contents(__DIR__ . '/../ajax/get_delay_table.php');
    test_assert_true(
        strpos($personalTable, "get_journal_period_from_session('delay_journal_period'") !== false,
        'Employee journal must keep its own period selection'
    );
    foreach (array(
        'time_approvement.php' => 'add_time_notification_period',
        'pause_view.php' => 'pause_notification_period',
        'accounting_errors_approvement.php' => 'accounting_errors_notification_period',
    ) as $pageName => $prefix) {
        $page = file_get_contents(__DIR__ . '/../' . $pageName);
        test_assert_true(
            strpos($page, "load_supervisor_journal_period('$prefix'") !== false
                && strpos($page, 'render_supervisor_journal_period_filter(') !== false,
            'Supervisor list must select and display its period in ' . $pageName
        );
    }
    foreach (array(
        'time_approvement_user.php' => 'add_time_notification_period',
        'pause_view_user.php' => 'pause_notification_period',
        'accounting_errors_approvement_user.php' => 'accounting_errors_notification_period',
    ) as $pageName => $prefix) {
        test_assert_true(
            strpos(file_get_contents(__DIR__ . '/../' . $pageName), "get_journal_period_from_session('$prefix'") !== false,
            'Supervisor detail must reuse the list period in ' . $pageName
        );
    }
    foreach (array(
        'ajax/get_add_times_table.php' => 'add_time_journal_period',
        'ajax/get_delay_table.php' => 'delay_journal_period',
        'ajax/get_pause_times_table.php' => 'pause_journal_period',
    ) as $pageName => $prefix) {
        test_assert_true(
            strpos(file_get_contents(__DIR__ . '/../' . $pageName), $prefix . '_type') !== false,
            'Employee journal must have a period selector in ' . $pageName
        );
    }
    $script = file_get_contents(__DIR__ . '/../js/tory.js');
    test_assert_true(
        strpos($script, "manualPeriod.hidden = !manual") !== false
            && strpos($script, "dateInputs[index].disabled = !manual") !== false,
        'Manual dates must remain hidden and disabled for preset periods'
    );
    $presetForm = render_supervisor_journal_period_filter(
        'delay_approvement.php', 'delay_notification_period',
        get_journal_period(4, null, null, '2026-10-07')
    );
    test_assert_true(
        strpos($presetForm, 'hidden style="display:none;"') !== false
            && substr_count($presetForm, ' disabled') === 2
            && strpos($presetForm, 'Задать вручную') !== false,
        'Preset periods must hide and disable manual dates'
    );
    $manualForm = render_supervisor_journal_period_filter(
        'delay_approvement.php', 'delay_notification_period',
        get_journal_period(7, '2026-08-01', '2026-08-31', '2026-10-07')
    );
    test_assert_same(0, substr_count($manualForm, ' disabled'), 'Manual dates must remain editable');
    test_assert_true(
        strpos($manualForm, 'value="2026-08-01"') !== false
            && strpos($manualForm, 'value="2026-08-31"') !== false,
        'Manual dates must survive a page refresh'
    );

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

        $_SESSION = array();
        $selected = load_supervisor_journal_period(
            'pause_notification_period', '2026-10-07',
            array('period_mode' => '7', 'start_date' => '2026-08-01', 'stop_date' => '2026-08-31')
        );
        test_assert_same('2026-08-01', $selected['period']['start_date'], 'Supervisor manual selection must persist');
        $detailPeriod = get_journal_period_from_session('pause_notification_period', '2026-10-07');
        test_assert_same($selected['period'], $detailPeriod, 'Supervisor detail must use the list period');
        $invalid = load_supervisor_journal_period(
            'pause_notification_period', '2026-10-07',
            array('period_mode' => '7', 'start_date' => '2026-09-01', 'stop_date' => '2026-08-31')
        );
        test_assert_true($invalid['error'] !== '', 'Invalid ranges must show a validation error');
        test_assert_same($selected['period'], $invalid['period'], 'Invalid ranges must preserve the last good period');
    } finally {
        $_SESSION = $savedSession;
    }
};
