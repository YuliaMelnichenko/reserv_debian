<?php

require_once __DIR__ . '/../../inc/notification_decision_service.php';

return function ($link) {
    $supervisorID = 901;
    $employeeID = 902;
    $date = '2026-08-21';

    integration_seed_employee($link, $supervisorID, 'Руководитель');
    integration_seed_employee($link, $employeeID, 'Сотрудник', $supervisorID);

    test_assert_same(
        true,
        db_execute(
            $link,
            'INSERT INTO ADD_TIME (ID, ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE) VALUES (?, ?, -1, ?, ?, ?, 1, ?, \'\', 0, 0)',
            'isisss',
            array(701, $date, $employeeID, $date . ' 10:00:00', $date . ' 11:00:00', 'Выезд к заказчику')
        ),
        'Remote-work fixture must be created'
    );

    test_assert_same(true, notification_decision_update_add_time($link, 701, $supervisorID, 'Согласовано', 1), 'Supervisor must be able to accept remote work');
    $addTime = db_fetch_one(db_query($link, 'SELECT SUIR, SUPERVISORDESC, APPROVED FROM ADD_TIME WHERE ID = ?', 'i', array(701)));
    test_assert_same($supervisorID, (int)$addTime['SUIR'], 'Accepted remote work must retain the supervisor');
    test_assert_same('Согласовано', $addTime['SUPERVISORDESC'], 'Accepted remote work must retain the comment');
    test_assert_same(1, (int)$addTime['APPROVED'], 'Accepted remote work must have status 1');

    test_assert_same(true, notification_decision_set_add_time_deleted($link, 701, 100), 'Supervisor must be able to mark remote work as deleted');
    $addTimeDeleted = db_fetch_one(db_query($link, 'SELECT APPROVED FROM ADD_TIME WHERE ID = ?', 'i', array(701)));
    test_assert_same(101, (int)$addTimeDeleted['APPROVED'], 'Deleted accepted remote work must retain its decision state');
    test_assert_same(true, notification_decision_set_add_time_deleted($link, 701, 200), 'Supervisor must be able to restore remote work');

    test_assert_same(
        true,
        db_execute($link, 'INSERT INTO Delays (ID, date, duration, userID, explaneDesk, status) VALUES (?, ?, ?, ?, ?, 0)', 'issis', array(801, $date, '00:15:00', $employeeID, 'Пробка')),
        'Delay fixture must be created'
    );

    test_assert_same(true, notification_decision_update_delay($link, 801, $supervisorID, 'Не согласовано', -1), 'Supervisor must be able to refuse a delay');
    $delay = db_fetch_one(db_query($link, 'SELECT acceptorID, penaltyID, penaltyReply, status FROM Delays WHERE ID = ?', 'i', array(801)));
    test_assert_same($supervisorID, (int)$delay['acceptorID'], 'Refused delay must retain the acceptor');
    test_assert_same(-1, (int)$delay['status'], 'Refused delay must have status -1');
    test_assert_true((int)$delay['penaltyID'] > 0, 'Refused delay must create a penalty');

    $penalty = db_fetch_one(db_query($link, 'SELECT reason FROM Penalty WHERE ID = ? AND userID = ?', 'ii', array((int)$delay['penaltyID'], $employeeID)));
    test_assert_same('Не согласовано', $penalty['reason'], 'Refused delay must retain the penalty comment');

    test_assert_same(true, notification_decision_update_delay($link, 801, $supervisorID, 'Принято после проверки', 1), 'Supervisor must be able to accept a previously refused delay');
    $acceptedDelay = db_fetch_one(db_query($link, 'SELECT penaltyID, penaltyReply, status FROM Delays WHERE ID = ?', 'i', array(801)));
    test_assert_same(1, (int)$acceptedDelay['status'], 'Accepted delay must have status 1');
    test_assert_same(-1, (int)$acceptedDelay['penaltyID'], 'Accepted delay must not retain a penalty');
    test_assert_same('Принято после проверки', $acceptedDelay['penaltyReply'], 'Accepted delay must retain the decision comment');

    test_assert_same(true, notification_decision_set_delay_deleted($link, 801, 100), 'Supervisor must be able to mark a delay as deleted');
    $deletedDelay = db_fetch_one(db_query($link, 'SELECT status FROM Delays WHERE ID = ?', 'i', array(801)));
    test_assert_same(101, (int)$deletedDelay['status'], 'Deleted accepted delay must retain its decision state');
    test_assert_same(true, notification_decision_set_delay_deleted($link, 801, 200), 'Supervisor must be able to restore a delay');
};
