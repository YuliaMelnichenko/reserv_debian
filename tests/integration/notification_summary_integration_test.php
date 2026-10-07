<?php

require_once __DIR__ . '/../../inc/notification_summary.php';
require_once __DIR__ . '/../../inc/delay_journal.php';
require_once __DIR__ . '/../../inc/accounting_errors.php';
require_once __DIR__ . '/../../inc/pause_service.php';

return function ($link) {
    $supervisorId = 501;
    $alphaId = 502;
    $betaId = 503;
    $currentDateTime = date('Y-m-d 12:00:00');
    $currentDate = substr($currentDateTime, 0, 10);
    list($previousQuarterStart) = get_delay_notification_period_date_range($currentDate);
    $previousQuarterWeekday = date('Y-m-d', strtotime(
        $previousQuarterStart . ' +' . ((8 - (int)date('N', strtotime($previousQuarterStart))) % 7) . ' days'
    ));
    $previousQuarterWithoutVisit = date('Y-m-d', strtotime($previousQuarterWeekday . ' +1 day'));

    integration_seed_employee($link, $supervisorId, 'Руководитель');
    integration_seed_employee($link, $alphaId, 'Альфа');
    integration_seed_employee($link, $betaId, 'Бета');

    foreach (array(
        array($alphaId, '0'),
        array($alphaId, '3'),
        array($betaId, '0'),
        array($betaId, '3'),
        array($alphaId, '4'),
    ) as $membership) {
        test_assert_same(
            true,
            db_execute(
                $link,
                'INSERT INTO `GROUPS` (USERID, SUPERVISORID, TYPE) VALUES (?, ?, ?)',
                'iis',
                array($membership[0], $supervisorId, $membership[1])
            ),
            'Notification membership fixture must be created'
        );
    }

    test_assert_same(
        true,
        db_execute(
            $link,
            "INSERT INTO visiting (ID, user_id, in_dt, eat_start_dt, eat_stop_dt, out_dt, state, remoteWorkState)
             VALUES
                (1, ?, ?, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2, 0),
                (2, ?, ?, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2, 0),
                (3, ?, ?, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2, 0)",
            'isisis',
            array($alphaId, $currentDate . ' 09:31:00', $betaId, $currentDate . ' 09:32:00', $alphaId, $previousQuarterWeekday . ' 09:31:00')
        ),
        'Delay visit fixtures must be created'
    );

    foreach (array(
        array(1, $currentDate, '00:01:00', $alphaId, 'Без объяснения', 0),
        array(2, $currentDate, '00:02:00', $betaId, 'Согласовано', 1),
        array(3, $previousQuarterWeekday, '00:01:00', $alphaId, 'Прошлый квартал', 0),
        array(4, $previousQuarterWithoutVisit, '00:03:00', $betaId, 'Историческая запись', 1),
    ) as $delayFixture) {
        test_assert_same(
            true,
            db_execute(
                $link,
                'INSERT INTO Delays (ID, date, duration, userID, explaneDesk, status) VALUES (?, ?, ?, ?, ?, ?)',
                'issisi',
                $delayFixture
            ),
            'Delay fixture must be created'
        );
    }

    $acceptedDelay = db_fetch_one(db_query(
        $link,
        'SELECT status FROM Delays WHERE ID = ? AND userID = ?',
        'ii',
        array(2, $betaId)
    ));
    test_assert_same(1, (int)$acceptedDelay['status'], 'Accepted delay fixture must retain status 1');

    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO ADD_TIME (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'siississii',
            array(
                $currentDate, $supervisorId, $alphaId, $currentDate . ' 10:00:00', $currentDate . ' 11:00:00', 1, 'Выезд', '', 0, 0,
            )
        ),
        'Time notification fixtures must be created'
    );

    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO ADD_TIME (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'siississii',
            array(
                date('Y-m-d', strtotime($currentDate . ' +2 days')),
                $supervisorId,
                $betaId,
                date('Y-m-d 10:00:00', strtotime($currentDate . ' +2 days')),
                date('Y-m-d 11:00:00', strtotime($currentDate . ' +2 days')),
                1,
                'Будущая запись',
                '',
                0,
                0,
            )
        ),
        'Future offsite-work fixture must be created'
    );

    $pauseStarted = start_time_pause(
        $link,
        $alphaId,
        1,
        $supervisorId,
        $currentDate,
        $currentDate . ' 12:00:00',
        'Встреча'
    );
    test_assert_same('success', $pauseStarted['status'], 'Pause notification fixture must be started through the application service');
    $pause = db_fetch_one(db_query(
        $link,
        'SELECT ID FROM ADD_TIME WHERE USERID = ? AND PAUSE_MODE = 1 ORDER BY ID DESC LIMIT 1',
        'i',
        array($alphaId)
    ));
    $pauseFinished = finish_time_pause($link, $alphaId, 1, (int) $pause['ID'], $currentDate . ' 12:30:00');
    test_assert_same('success', $pauseFinished['status'], 'Pause notification fixture must be completed through the application service');

    $delaySummary = get_delay_notification_summary($link, $supervisorId, $currentDate);
    list($currentQuarterStart) = get_current_quarter_date_range(false, $currentDate);
    test_assert_same($currentQuarterStart, $delaySummary['period_start_date'], 'Supervisor delay summary must default to the current quarter');
    test_assert_same(1, $delaySummary['entries'][0]['new_count'], 'Default summary must exclude previous-quarter delays');
    test_assert_same(1, $delaySummary['entries'][1]['accepted_count'], 'Default summary must exclude previous-quarter approvals');
    $delaySummary = get_delay_notification_summary($link, $supervisorId, $currentDate, array(
        'start_date' => $previousQuarterStart,
        'stop_date' => $currentDate,
        'stop_exclusive' => date('Y-m-d', strtotime($currentDate . ' +1 day')),
    ));
    test_assert_same($previousQuarterStart, $delaySummary['period_start_date'], 'Explicit summary period must include the previous quarter');
    $delayEntriesByUserId = array();

    foreach ($delaySummary['entries'] as $entry) {
        $delayEntriesByUserId[$entry['user_id']] = $entry;
    }

    test_assert_same(array($alphaId, $betaId), array_column($delaySummary['entries'], 'user_id'), 'Delay entries must be sorted by surname');
    test_assert_same(2, $delayEntriesByUserId[$alphaId]['new_count'], 'Supervisor must see open delays from both quarters');
    test_assert_same(1, $delayEntriesByUserId[$alphaId]['without_comment_count'], 'Unexplained delay must be marked separately');
    $delayJournal = get_delay_journal_context($link, $alphaId, $currentDate, true, array(
        'start_date' => $previousQuarterStart,
        'stop_date' => $delaySummary['period_stop_date'],
        'stop_exclusive' => $delaySummary['period_stop_exclusive'],
    ));
    test_assert_true(
        in_array($previousQuarterWeekday, array_column($delayJournal['entries'], 'date'), true),
        'Supervisor detail must include the previous-quarter delay'
    );
    $personalDelayJournal = get_delay_journal_context($link, $alphaId, $currentDate, false);
    test_assert_same(
        $currentQuarterStart,
        $personalDelayJournal['period_start_date'],
        'Employee delay journal must default to the current quarter'
    );
    test_assert_true(
        !in_array($previousQuarterWeekday, array_column($personalDelayJournal['entries'], 'date'), true),
        'Default employee delay journal must exclude the previous-quarter delay'
    );
    test_assert_same(
        2,
        $delayEntriesByUserId[$betaId]['accepted_count'],
        'Accepted delay must remain visible in history: ' . json_encode($delayEntriesByUserId[$betaId])
    );
    $betaDelayJournal = get_delay_journal_context($link, $betaId, $currentDate, true, array(
        'start_date' => $previousQuarterStart,
        'stop_date' => $delaySummary['period_stop_date'],
        'stop_exclusive' => $delaySummary['period_stop_exclusive'],
    ));
    $historicalDelay = array_values(array_filter($betaDelayJournal['entries'], function ($entry) use ($previousQuarterWithoutVisit) {
        return $entry['date'] === $previousQuarterWithoutVisit;
    }));
    test_assert_same(1, count($historicalDelay), 'A stored delay must remain visible when its visit is missing');
    test_assert_same(180, $historicalDelay[0]['duration'], 'Historical delay duration must come from the stored record');

    $pauseSummary = get_pause_notification_summary($link, $supervisorId, $currentDateTime);
    test_assert_same(1, count($pauseSummary['entries']), 'Pause notifications must include only assigned employees');
    test_assert_same($alphaId, $pauseSummary['entries'][0]['user_id'], 'Pause notification must belong to the assigned employee');
    test_assert_same(
        1,
        $pauseSummary['entries'][0]['current_day_count'],
        'Current-day pause count must be accurate: ' . json_encode($pauseSummary['entries'][0])
    );

    $offsiteSummary = get_add_time_notification_summary($link, $supervisorId, $currentDateTime);
    test_assert_same(2, count($offsiteSummary['entries']), 'Offsite summary must include every assigned employee');
    test_assert_same($alphaId, $offsiteSummary['entries'][0]['user_id'], 'Offsite summary must be sorted by surname');
    test_assert_same(1, $offsiteSummary['entries'][0]['new_count'], 'Unapproved offsite work must be visible');

    $menuCounts = get_supervisor_notification_counts($link, $supervisorId, $currentDateTime);
    test_assert_same(1, $menuCounts['add_time_count'], 'Offsite menu counter must include only new records from the displayed period');
    test_assert_same(2, $menuCounts['delay_count'], 'Delay menu counter must include open records from both quarters');
    list($errorPeriodStart, $errorPeriodStop) = accounting_errors_get_range();
    foreach (array(0, 1, 2, 3, 4) as $status) {
        $errorUserId = 510 + $status;
        integration_seed_employee($link, $errorUserId, 'Status fixture', $supervisorId);
        test_assert_same(
            true,
            db_execute(
                $link,
                'INSERT INTO accounting_errors (USERID, ERROR_DATE, STATUS, COMMENT, CREATED_DT) VALUES (?, ?, ?, ?, NOW())',
                'isis',
                array($errorUserId, $errorPeriodStop, $status, '')
            ),
            'Accounting-error fixture must be created'
        );
    }

    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO staff_leaves (user_id, fio, start_date, stop_date, event) VALUES (?, ?, ?, ?, ?)',
            'issss',
            array($betaId, 'Бета Тест Тестович', $errorPeriodStop, $errorPeriodStop, 'Командировка')
        ),
        'Business-trip fixture must be created'
    );

    test_assert_same(
        $errorPeriodStart <= $errorPeriodStop ? 4 : 0,
        get_accounting_errors_notification_count($link, $supervisorId),
        'Accounting-error menu counter must include all active errors and outstanding business trips'
    );
};
