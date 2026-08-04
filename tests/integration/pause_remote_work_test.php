<?php

require_once __DIR__ . '/../../inc/pause_service.php';
require_once __DIR__ . '/../../inc/remote_work.php';

return function ($link) {
    integration_seed_employee($link, 201, 'Руководитель');
    integration_seed_employee($link, 101, 'Сотрудник', 201);
    $visitCreated = db_execute(
        $link,
        "INSERT INTO visiting (
            ID, user_id, in_dt, eat_start_dt, eat_stop_dt, out_dt, state, take_pause
         ) VALUES (
            1, 101, '2026-07-28 08:00:00', '0000-00-00 00:00:00',
            '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2, 0
         )"
    );
    test_assert_same(true, $visitCreated, 'Integration visit fixture must be created');

    $pauseResult = start_time_pause(
        $link,
        101,
        1,
        201,
        '2026-07-28',
        '2026-07-28 10:00:00',
        'Рабочая встреча'
    );
    test_assert_same('success', $pauseResult['status'], 'Employee must start a time pause');

    $pause = db_fetch_one(db_query(
        $link,
        'SELECT ID, STOP_DT, DESCRIPTION FROM ADD_TIME WHERE USERID = ? AND PAUSE_MODE = 1',
        'i',
        array(101)
    ));
    test_assert_same('Рабочая встреча', $pause['DESCRIPTION'], 'Pause comment must be persisted');

    $finishPause = finish_time_pause($link, 101, 1, (int)$pause['ID'], '2026-07-28 10:30:00');
    test_assert_same('success', $finishPause['status'], 'Employee must finish a time pause');

    $closedPause = db_fetch_one(db_query(
        $link,
        'SELECT STOP_DT FROM ADD_TIME WHERE ID = ?',
        'i',
        array((int)$pause['ID'])
    ));
    test_assert_same('2026-07-28 10:30:00', $closedPause['STOP_DT'], 'Pause finish must be persisted');

    $remoteStart = start_remote_work($link, 101, 201);
    test_assert_same('success', $remoteStart['status'], 'Employee must start remote work');

    $openRemoteWork = get_open_remote_work($link, 101);
    test_assert_true(is_array($openRemoteWork), 'Started remote work must be visible');
    test_assert_same(201, (int)$openRemoteWork['supervisor_id'], 'Remote work supervisor must be persisted');

    $remoteFinish = finish_remote_work($link, 101);
    test_assert_same('success', $remoteFinish['status'], 'Employee must finish remote work');
    test_assert_same(null, get_open_remote_work($link, 101), 'Finished remote work must no longer be open');
};
