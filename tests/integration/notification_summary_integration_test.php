<?php

require_once __DIR__ . '/../../inc/notification_summary.php';

return function ($link) {
    $supervisorId = 501;
    $alphaId = 502;
    $betaId = 503;
    $currentDateTime = date('Y-m-d 12:00:00');
    $currentDate = substr($currentDateTime, 0, 10);

    integration_seed_employee($link, $supervisorId, 'Руководитель');
    integration_seed_employee($link, $alphaId, 'Альфа');
    integration_seed_employee($link, $betaId, 'Бета');

    foreach (array(
        array($alphaId, '0'),
        array($betaId, '0'),
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
                (2, ?, ?, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2, 0)",
            'isis',
            array($alphaId, $currentDate . ' 09:31:00', $betaId, $currentDate . '09:32:00')
        ),
        'Delay visit fixtures must be created'
    );

    foreach (array(
        array(1, $currentDate, '00:01:00', $alphaId, 'Без объяснения', 0),
        array(2, $currentDate, '00:02:00', $betaId, 'Согласовано', 1),
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

    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO ADD_TIME (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'siississiisiississii',
            array(
                $currentDate, $supervisorId, $alphaId, $currentDate . ' 10:00:00', $currentDate . ' 11:00:00', 1, 'Выезд', '', 0, 0,
                $currentDate, $supervisorId, $alphaId, $currentDate . '12:00:00', $currentDate . ' 12:30:00', 1, 'Встреча', '', 0, 1,
            )
        ),
        'Time notification fixtures must be created'
    );

    $delaySummary = get_delay_notification_summary($link, $supervisorId, $currentDate);
    $delayEntriesByUserId = array();

    foreach ($delaySummary['entries'] as $entry) {
        $delayEntriesByUserId[$entry['user_id']] = $entry;
    }

    test_assert_same(array($alphaId, $betaId), array_column($delaySummary['entries'], 'user_id'), 'Delay entries must be sorted by surname');
    test_assert_same(1, $delayEntriesByUserId[$alphaId]['new_count'], 'New delay must be visible to the supervisor');
    test_assert_same(1, $delayEntriesByUserId[$alphaId]['without_comment_count'], 'Unexplained delay must be marked separately');
    test_assert_same(1, $delayEntriesByUserId[$betaId]['accepted_count'], 'Accepted delay must remain visible in history');

    $pauseSummary = get_pause_notification_summary($link, $supervisorId, $currentDateTime);
    test_assert_same(1, count($pauseSummary['entries']), 'Pause notifications must include only assigned employees');
    test_assert_same($alphaId, $pauseSummary['entries'][0]['user_id'], 'Pause notification must belong to the assigned employee');
    test_assert_same(1, $pauseSummary['entries'][0]['current_day_count'], 'Current-day pause count must be accurate');

    $offsiteSummary = get_add_time_notification_summary($link, $supervisorId, $currentDateTime);
    test_assert_same(2, count($offsiteSummary['entries']), 'Offsite summary must include every assigned employee');
    test_assert_same($alphaId, $offsiteSummary['entries'][0]['user_id'], 'Offsite summary must be sorted by surname');
    test_assert_same(1, $offsiteSummary['entries'][0]['new_count'], 'Unapproved offsite work must be visible');
};
