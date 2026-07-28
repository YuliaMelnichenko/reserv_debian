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
};
