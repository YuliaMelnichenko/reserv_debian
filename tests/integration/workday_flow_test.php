<?php

require_once __DIR__ . '/../../inc/workday_transition_service.php';

return function ($link) {
    integration_seed_employee($link, 101, 'Рабочий');
    $session = array();
    $context = array(
        'user_id' => 101,
        'visit_id' => 0,
        'period_start' => '2026-07-28 06:00:00',
        'period_stop' => '2026-07-29 06:00:00',
        'now' => '2026-07-28 08:30:00',
        'max_open_shift_seconds' => 10800,
        'target_state' => 2,
    );

    test_assert_same('1', workday_transition_arrive($link, $session, $context), 'Employee must arrive');
    test_assert_true((int)$session['ss_visiting_ID'] > 0, 'Arrival must create a visiting row');

    $context['visit_id'] = (int)$session['ss_visiting_ID'];
    $context['now'] = '2026-07-28 12:00:00';
    $context['target_state'] = 3;
    test_assert_same('1', workday_transition_start_lunch($link, $session, $context), 'Employee must start lunch');

    $context['now'] = '2026-07-28 13:00:00';
    $context['target_state'] = 4;
    test_assert_same('1', workday_transition_finish_lunch($link, $session, $context), 'Employee must finish lunch');

    $context['now'] = '2026-07-28 17:30:00';
    $context['target_state'] = 0;
    test_assert_same('1', workday_transition_leave($link, $session, $context), 'Employee must leave work');

    $visit = db_fetch_one(db_query(
        $link,
        'SELECT state, in_dt, eat_start_dt, eat_stop_dt, out_dt FROM visiting WHERE user_id = ?',
        'i',
        array(101)
    ));

    test_assert_same(0, (int)$visit['state'], 'Completed workday must be closed');
    test_assert_same('2026-07-28 08:30:00', $visit['in_dt'], 'Arrival time must be persisted');
    test_assert_same('2026-07-28 12:00:00', $visit['eat_start_dt'], 'Lunch start must be persisted');
    test_assert_same('2026-07-28 13:00:00', $visit['eat_stop_dt'], 'Lunch finish must be persisted');
    test_assert_same('2026-07-28 17:30:00', $visit['out_dt'], 'Leave time must be persisted');

    integration_seed_employee($link, 102, 'Опоздавший');
    $lateSession = array();
    $lateContext = array(
        'user_id' => 102,
        'visit_id' => 0,
        'period_start' => '2026-07-29 06:00:00',
        'period_stop' => '2026-07-30 06:00:00',
        'now' => '2026-07-29 09:30:01',
        'max_open_shift_seconds' => 10800,
        'target_state' => 2,
    );

    test_assert_same(
        '1',
        workday_transition_arrive($link, $lateSession, $lateContext),
        'A delayed employee must still be registered for work'
    );

    $delay = db_fetch_one(db_query(
        $link,
        'SELECT date, duration, explaneDesk, status FROM Delays WHERE userID = ?',
        'i',
        array(102)
    ));

    test_assert_same('2026-07-29', $delay['date'], 'A delay must be stored on the arrival date');
    test_assert_same('00:00:01', $delay['duration'], 'The stored delay duration must respect the allowed boundary');
    test_assert_same('Без объяснения', $delay['explaneDesk'], 'A delay must be visible before an employee submits a comment');
    test_assert_same(0, (int)$delay['status'], 'A delay without a comment must remain under review');
    test_assert_same(2, (int)$lateSession['ss_there_is_delay'], 'The employee must still be offered an explanation after arrival');

    integration_seed_employee($link, 103, 'Старая смена');
    test_assert_same(
        true,
        db_execute(
            $link,
            "INSERT INTO visiting (ID, user_id, in_dt, state) VALUES (50, 103, '2021-07-28 08:00:00', 3)"
        ),
        'A historical open visit fixture must be created'
    );

    $periodStart = '2026-07-30 00:00:00';
    $periodStop = '2026-07-31 00:00:00';
    $now = '2026-07-30 01:00:00';
    $currentState = sync_time_registration_state_from_db($link, 103, $periodStart, $periodStop, $now, 10800);
    test_assert_same(1, $currentState['state'], 'A visit from 2021 must not be treated as the current shift');
    test_assert_same(0, $currentState['visiting_ID'], 'A historical visit must be removed from the current session');

    $newSession = array();
    test_assert_same(
        '1',
        workday_transition_arrive($link, $newSession, array(
            'user_id' => 103,
            'period_start' => $periodStart,
            'period_stop' => $periodStop,
            'now' => $now,
            'max_open_shift_seconds' => 10800,
            'target_state' => 2,
        )),
        'A historical open visit must not block a new arrival'
    );
    $historicalVisit = db_fetch_one(db_query($link, 'SELECT state FROM visiting WHERE ID = 50'));
    test_assert_same(3, (int)$historicalVisit['state'], 'Historical visits must remain unchanged');

    integration_seed_employee($link, 104, 'Ночная смена');
    test_assert_same(
        true,
        db_execute(
            $link,
            "INSERT INTO visiting (ID, user_id, in_dt, state) VALUES (52, 104, '2026-07-29 23:30:00', 3)"
        ),
        'A recent overnight visit fixture must be created'
    );
    $overnightState = sync_time_registration_state_from_db($link, 104, $periodStart, $periodStop, $now, 10800);
    test_assert_same(3, $overnightState['state'], 'A recent overnight shift must remain available');
    test_assert_same(52, $overnightState['visiting_ID'], 'The overnight visit must retain its ID');
};
