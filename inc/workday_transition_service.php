<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/workday_state.php';
require_once __DIR__ . '/workday_registration.php';

function workday_transition_database_error($link)
{
    return ajax_database_error_message($link, __FILE__ . ':' . __LINE__);
}

function workday_transition_arrive($link, &$session, $context)
{
    $userId = $context['user_id'];
    $session['ss_visiting_ID'] = 0;
    $transaction = db_transaction_start($link);

    if (!$transaction) {
        return workday_transition_database_error($link);
    }

    $idResult = db_query($link, 'SELECT ID FROM visiting ORDER BY ID DESC LIMIT 1 FOR UPDATE');

    if (!$idResult) {
        $transaction->rollback();
        return workday_transition_database_error($link);
    }

    $lastVisit = db_fetch_one($idResult);
    $newId = $lastVisit ? (int)$lastVisit['ID'] + 1 : 1;
    $openResult = db_query($link, "
        SELECT ID, in_dt, state
        FROM visiting
        WHERE user_id = ?
          AND state != 0
          AND (
            (in_dt >= ? AND in_dt < ?)
            OR (
              in_dt < ?
              AND TIMESTAMPDIFF(SECOND, ?, ?) <= ?
            )
          )
        ORDER BY in_dt DESC, ID DESC
        LIMIT 1
        FOR UPDATE
    ", 'isssssi', array(
        $userId,
        $context['period_start'],
        $context['period_stop'],
        $context['period_start'],
        $context['period_start'],
        $context['now'],
        $context['max_open_shift_seconds'],
    ));

    if (!$openResult) {
        $transaction->rollback();
        return workday_transition_database_error($link);
    }

    $openVisit = db_fetch_one($openResult);

    if ($openVisit) {
        if (!$transaction->commit()) {
            return workday_transition_database_error($link);
        }

        $session['ss_state'] = (int)$openVisit['state'];
        $session['ss_visiting_ID'] = (int)$openVisit['ID'];

        error_log(
            'TORI_SWITCH_BLOCK_INSERT_RECENT user=' . $userId
            . ' open_visit=' . $openVisit['ID']
            . ' open_state=' . $openVisit['state']
            . ' open_in=' . $openVisit['in_dt']
        );

        return 'Ошибка: у сотрудника уже есть открытый рабочий день от '
            . $openVisit['in_dt']
            . '. Новый приход не создан. Обновите страницу.';
    }

    $created = db_execute($link, "
        INSERT INTO visiting (
          ID,
          user_id,
          in_dt,
          eat_start_dt,
          eat_stop_dt,
          out_dt,
          state,
          remoteWorkState,
          dayTransitionTime
        )
        SELECT DISTINCT
          ?,
          ?,
          ?,
          '0000-00-00 00:00:00',
          '0000-00-00 00:00:00',
          '0000-00-00 00:00:00',
          '2',
          b.RemoteWork,
          b.dayTransitionTime
        FROM employees b
        WHERE b.ID = ?
    ", 'iisi', array($newId, $userId, $context['now'], $userId));

    if (!$created) {
        $transaction->rollback();
        return workday_transition_database_error($link);
    }

    if (!$transaction->commit()) {
        return workday_transition_database_error($link);
    }

    $session['ss_state'] = $context['target_state'];
    $session['ss_visiting_ID'] = $newId;

    return '1';
}

function workday_transition_require_visit($link, $context, $expectedState)
{
    return require_current_visit_row(
        $link,
        $context['user_id'],
        $context['visit_id'],
        $context['period_start'],
        $context['period_stop'],
        $context['now'],
        $context['max_open_shift_seconds'],
        $expectedState
    );
}

function workday_transition_update_visit($link, &$session, $context, $sql, $types, $params, $errorMessage)
{
    $affectedRows = db_execute_affected_rows($link, $sql, $types, $params);

    if ($affectedRows === false) {
        return workday_transition_database_error($link);
    }

    if ($affectedRows <= 0) {
        return $errorMessage;
    }

    $session['ss_state'] = $context['target_state'];

    return '1';
}

function workday_transition_start_lunch($link, &$session, $context)
{
    $visit = workday_transition_require_visit($link, $context, 2);

    if (strtotime($context['now']) <= strtotime($visit['in_dt'])) {
        return 'Ошибка: время начала обеда не может быть меньше или равно времени прихода.';
    }

    return workday_transition_update_visit(
        $link,
        $session,
        $context,
        'UPDATE visiting SET eat_start_dt = ?, state = 3 WHERE user_id = ? AND ID = ?',
        'sii',
        array($context['now'], $context['user_id'], $context['visit_id']),
        'Ошибка: не удалось начать обед. Обновите страницу.'
    );
}

function workday_transition_finish_lunch($link, &$session, $context)
{
    $visit = workday_transition_require_visit($link, $context, 3);

    if ($visit['eat_start_dt'] === '0000-00-00 00:00:00') {
        return 'Ошибка: нельзя завершить обед, потому что время начала обеда не найдено.';
    }

    if (strtotime($context['now']) <= strtotime($visit['eat_start_dt'])) {
        return 'Ошибка: время окончания обеда не может быть меньше или равно времени начала обеда.';
    }

    return workday_transition_update_visit(
        $link,
        $session,
        $context,
        'UPDATE visiting SET eat_stop_dt = ?, state = 4 WHERE user_id = ? AND ID = ?',
        'sii',
        array($context['now'], $context['user_id'], $context['visit_id']),
        'Ошибка: не удалось завершить обед. Обновите страницу.'
    );
}

function workday_transition_leave($link, &$session, $context)
{
    $visit = workday_transition_require_visit($link, $context, 4);
    $visitId = (int)$visit['ID'];

    if ((int)$visit['state'] === 0) {
        $session['ss_state'] = 0;
        $session['ss_visiting_ID'] = $visitId;
        return '1';
    }

    if (strtotime($context['now']) <= strtotime($visit['in_dt'])) {
        return 'Ошибка: время ухода не может быть меньше или равно времени прихода.';
    }

    $affectedRows = db_execute_affected_rows(
        $link,
        'UPDATE visiting SET out_dt = ?, state = 0 WHERE user_id = ? AND ID = ? AND state = 4',
        'sii',
        array($context['now'], $context['user_id'], $visitId)
    );

    if ($affectedRows === false) {
        return workday_transition_database_error($link);
    }

    if (!db_execute(
        $link,
        'UPDATE remote_work SET stop_dt = NOW() WHERE user_id = ? AND stop_dt IS NULL',
        'i',
        array($context['user_id'])
    )) {
        return workday_transition_database_error($link);
    }

    if ($affectedRows <= 0) {
        $currentResult = db_query(
            $link,
            'SELECT ID, state, out_dt FROM visiting WHERE user_id = ? AND ID = ? LIMIT 1',
            'ii',
            array($context['user_id'], $visitId)
        );
        $currentVisit = db_fetch_one($currentResult);

        if ($currentVisit && (int)$currentVisit['state'] === 0) {
            $session['ss_state'] = 0;
            $session['ss_visiting_ID'] = $visitId;
            return '1';
        }

        return 'Ошибка: не удалось зарегистрировать уход. Обновите страницу.';
    }

    $session['ss_state'] = $context['target_state'];
    $session['ss_visiting_ID'] = $visitId;

    return '1';
}

function workday_transition_undo($link, &$session, $context, $action)
{
    $operations = array(
        WORKDAY_ACTION_UNDO_FINISH_LUNCH => array(
            'state' => 4,
            'sql' => "UPDATE visiting
                      SET eat_stop_dt = '0000-00-00 00:00:00',
                          out_dt = '0000-00-00 00:00:00',
                          state = 3
                      WHERE user_id = ? AND ID = ?",
            'error' => 'Ошибка: не удалось выполнить откат состояния. Обновите страницу.',
        ),
        WORKDAY_ACTION_UNDO_START_LUNCH => array(
            'state' => 3,
            'sql' => "UPDATE visiting
                      SET eat_start_dt = '0000-00-00 00:00:00',
                          eat_stop_dt = '0000-00-00 00:00:00',
                          state = 2
                      WHERE user_id = ? AND ID = ?",
            'error' => 'Ошибка: не удалось выполнить откат состояния. Обновите страницу.',
        ),
        WORKDAY_ACTION_UNDO_ARRIVE => array(
            'state' => 2,
            'sql' => 'DELETE FROM visiting WHERE user_id = ? AND ID = ?',
            'error' => 'Ошибка: не удалось удалить приход. Обновите страницу.',
        ),
        WORKDAY_ACTION_UNDO_LEAVE => array(
            'state' => 0,
            'sql' => "UPDATE visiting
                      SET out_dt = '0000-00-00 00:00:00',
                          state = 4
                      WHERE user_id = ? AND ID = ?",
            'error' => 'Ошибка: не удалось выполнить откат ухода. Обновите страницу.',
        ),
    );

    if (!isset($operations[$action])) {
        return 'Ошибка: неизвестное состояние регистрации времени.';
    }

    $operation = $operations[$action];
    workday_transition_require_visit($link, $context, $operation['state']);
    $response = workday_transition_update_visit(
        $link,
        $session,
        $context,
        $operation['sql'],
        'ii',
        array($context['user_id'], $context['visit_id']),
        $operation['error']
    );

    if ($response === '1' && $action === WORKDAY_ACTION_UNDO_ARRIVE) {
        $session['ss_visiting_ID'] = 0;
    }

    return $response;
}

function process_workday_transition($link, &$session, $nextState)
{
    $dateTime = get_current_datetime_in_timezone()[1];
    $userId = (int)$session['ss_id'];
    $transitionTime = $session['ss_dayTransitionTime'] ?? '06:00:00';
    $period = datetimestr_to_day_start_stop_DT_ex_str_idx($dateTime, $transitionTime);
    $maxOpenShiftSeconds = 3 * 60 * 60;
    $syncedState = sync_time_registration_state_from_db(
        $link,
        $userId,
        $period[0],
        $period[1],
        $dateTime,
        $maxOpenShiftSeconds
    );
    $transition = get_workday_transition((int)$syncedState['state'], (int)$nextState);

    if ($transition === null) {
        return 'Ошибка: неизвестное состояние регистрации времени.';
    }

    $context = array(
        'user_id' => $userId,
        'visit_id' => (int)$syncedState['visiting_ID'],
        'period_start' => $period[0],
        'period_stop' => $period[1],
        'now' => $dateTime,
        'max_open_shift_seconds' => $maxOpenShiftSeconds,
        'target_state' => (int)$transition['to'],
    );
    $action = $transition['action'];

    if ($action === WORKDAY_ACTION_ARRIVE) {
        return workday_transition_arrive($link, $session, $context);
    }

    if ($action === WORKDAY_ACTION_START_LUNCH) {
        return workday_transition_start_lunch($link, $session, $context);
    }

    if ($action === WORKDAY_ACTION_FINISH_LUNCH) {
        return workday_transition_finish_lunch($link, $session, $context);
    }

    if ($action === WORKDAY_ACTION_LEAVE) {
        return workday_transition_leave($link, $session, $context);
    }

    if ($action === WORKDAY_ACTION_NOOP) {
        $session['ss_visiting_ID'] = 0;
        return '1';
    }

    return workday_transition_undo($link, $session, $context, $action);
}
