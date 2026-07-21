<?php

require_once __DIR__ . '/database.php';

function time_pause_result($status, $message = null)
{
    $result = array('status' => $status);

    if ($message !== null) {
        $result['message'] = $message;
    }

    return $result;
}

function start_sport_time_pause($link, $userID, $visitingID, $currentDate, $currentDateTime, $description)
{
    if ((int)$userID <= 0 || (int)$visitingID <= 0) {
        return time_pause_result('error', 'Не найдена активная запись рабочего дня');
    }

    $transaction = db_transaction_start($link);

    if (!$transaction) {
        return false;
    }

    $visitResult = db_query($link, "
        SELECT ID, state, take_pause
        FROM visiting
        WHERE ID = ?
          AND user_id = ?
        LIMIT 1
        FOR UPDATE
    ", 'ii', array((int)$visitingID, (int)$userID));

    if (!$visitResult) {
        $transaction->rollback();
        return false;
    }

    $visit = mysqli_fetch_assoc($visitResult);

    if (!$visit) {
        $transaction->rollback();
        return time_pause_result('error', 'Не найдена активная запись рабочего дня');
    }

    if (!in_array((int)$visit['state'], array(2, 4), true)) {
        $transaction->rollback();
        return time_pause_result('error', 'Приостановка учета времени сейчас недоступна');
    }

    $openPauseResult = db_query($link, "
        SELECT ID
        FROM ADD_TIME
        WHERE USERID = ?
          AND PAUSE_MODE = 1
          AND START_DT <> '0000-00-00 00:00:00'
          AND (STOP_DT IS NULL OR STOP_DT = '0000-00-00 00:00:00')
        ORDER BY START_DT DESC, ID DESC
        LIMIT 1
        FOR UPDATE
    ", 'i', array((int)$userID));

    if (!$openPauseResult) {
        $transaction->rollback();
        return false;
    }

    if (mysqli_num_rows($openPauseResult) > 0) {
        $visitUpdated = db_execute($link, "
            UPDATE visiting
            SET take_pause = 1
            WHERE ID = ?
              AND user_id = ?
        ", 'ii', array((int)$visitingID, (int)$userID));

        if (!$visitUpdated) {
            $transaction->rollback();
            return false;
        }

        if (!$transaction->commit()) {
            return false;
        }

        return time_pause_result('success');
    }

    $supervisorResult = db_query($link, "
        SELECT SUPERVISORID
        FROM GROUPS
        WHERE USERID = ?
          AND TRIM(TYPE) = '100'
        ORDER BY SUPERVISORID
        LIMIT 1
    ", 'i', array((int)$userID));

    if (!$supervisorResult) {
        $transaction->rollback();
        return false;
    }

    $supervisor = mysqli_fetch_assoc($supervisorResult);

    if (!$supervisor || (int)$supervisor['SUPERVISORID'] <= 0) {
        $transaction->rollback();
        return time_pause_result('error', 'Не найден руководитель для согласования');
    }

    $visitUpdated = db_execute($link, "
        UPDATE visiting
        SET take_pause = 1
        WHERE ID = ?
          AND user_id = ?
    ", 'ii', array((int)$visitingID, (int)$userID));

    if (!$visitUpdated) {
        $transaction->rollback();
        return false;
    }

    $pauseCreated = db_execute($link, "
        INSERT INTO ADD_TIME (
          ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION,
          SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT
        )
        VALUES (?, ?, ?, ?, '0000-00-00 00:00:00', -1, ?, '', 0, 1, 0)
    ", 'siiss', array(
        $currentDate,
        (int)$supervisor['SUPERVISORID'],
        (int)$userID,
        $currentDateTime,
        (string)$description,
    ));

    if (!$pauseCreated) {
        $transaction->rollback();
        return false;
    }

    if (!$transaction->commit()) {
        return false;
    }

    return time_pause_result('success');
}

function finish_time_pause($link, $userID, $visitingID, $pauseID, $currentDateTime)
{
    if ((int)$userID <= 0 || (int)$visitingID <= 0 || (int)$pauseID <= 0) {
        return time_pause_result('error', 'Не найдена открытая приостановка учета времени');
    }

    $transaction = db_transaction_start($link);

    if (!$transaction) {
        return false;
    }

    $visitResult = db_query($link, "
        SELECT ID
        FROM visiting
        WHERE ID = ?
          AND user_id = ?
        LIMIT 1
        FOR UPDATE
    ", 'ii', array((int)$visitingID, (int)$userID));

    if (!$visitResult) {
        $transaction->rollback();
        return false;
    }

    if (!mysqli_fetch_assoc($visitResult)) {
        $transaction->rollback();
        return time_pause_result('error', 'Не найдена активная запись рабочего дня');
    }

    $pauseResult = db_query($link, "
        SELECT ID, STOP_DT
        FROM ADD_TIME
        WHERE ID = ?
          AND USERID = ?
          AND PAUSE_MODE = 1
        LIMIT 1
        FOR UPDATE
    ", 'ii', array((int)$pauseID, (int)$userID));

    if (!$pauseResult) {
        $transaction->rollback();
        return false;
    }

    $pause = mysqli_fetch_assoc($pauseResult);

    if (!$pause) {
        $transaction->rollback();
        return time_pause_result('error', 'Не найдена открытая приостановка учета времени');
    }

    $visitUpdated = db_execute($link, "
        UPDATE visiting
        SET take_pause = 0
        WHERE ID = ?
          AND user_id = ?
    ", 'ii', array((int)$visitingID, (int)$userID));

    if (!$visitUpdated) {
        $transaction->rollback();
        return false;
    }

    $stopDateTime = isset($pause['STOP_DT']) ? $pause['STOP_DT'] : null;
    $isAlreadyClosed = is_string($stopDateTime)
        && $stopDateTime !== ''
        && $stopDateTime !== '0000-00-00 00:00:00';

    if (!$isAlreadyClosed) {
        $pauseUpdated = db_execute($link, "
            UPDATE ADD_TIME
            SET STOP_DT = ?
            WHERE ID = ?
              AND USERID = ?
              AND PAUSE_MODE = 1
        ", 'sii', array($currentDateTime, (int)$pauseID, (int)$userID));

        if (!$pauseUpdated) {
            $transaction->rollback();
            return false;
        }
    }

    if (!$transaction->commit()) {
        return false;
    }

    return time_pause_result('success');
}
