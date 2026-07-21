<?php

require_once __DIR__ . '/database.php';

function remote_work_result($status, $message = null)
{
    $result = array('status' => $status);

    if ($message !== null) {
        $result['message'] = $message;
    }

    return $result;
}

function finish_remote_work($link, $userID)
{
    $transaction = db_transaction_start($link);

    if (!$transaction) {
        return false;
    }

    $result = db_query($link, "
        SELECT id
        FROM remote_work
        WHERE user_id = ?
          AND DATE(start_dt) = CURDATE()
          AND stop_dt IS NULL
        ORDER BY id DESC
        LIMIT 1
        FOR UPDATE
    ", 'i', array((int)$userID));

    if (!$result) {
        $transaction->rollback();
        return false;
    }

    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        $transaction->rollback();
        return remote_work_result('error', 'Запись удалённой работы для завершения не найдена');
    }

    $affectedRows = db_execute_affected_rows($link, "
        UPDATE remote_work
        SET stop_dt = NOW()
        WHERE id = ?
          AND user_id = ?
          AND stop_dt IS NULL
        LIMIT 1
    ", 'ii', array((int)$row['id'], (int)$userID));

    if ($affectedRows === false) {
        $transaction->rollback();
        return false;
    }

    if ($affectedRows === 0) {
        $transaction->rollback();
        return remote_work_result('error', 'Запись удалённой работы для завершения не найдена');
    }

    if (!$transaction->commit()) {
        return false;
    }

    return remote_work_result('success');
}

function start_remote_work($link, $userID, $supervisorID)
{
    if ((int)$supervisorID <= 0) {
        return remote_work_result('error', 'Некорректный supervisor_id');
    }

    $transaction = db_transaction_start($link);

    if (!$transaction) {
        return false;
    }

    $supervisorResult = db_query($link, "
        SELECT 1
        FROM GROUPS
        WHERE USERID = ?
          AND SUPERVISORID = ?
          AND TRIM(TYPE) = '3'
        LIMIT 1
    ", 'ii', array((int)$userID, (int)$supervisorID));

    if (!$supervisorResult) {
        $transaction->rollback();
        return false;
    }

    if (mysqli_num_rows($supervisorResult) === 0) {
        $transaction->rollback();
        return remote_work_result('forbidden', 'Выбранный руководитель недоступен');
    }

    $openResult = db_query($link, "
        SELECT id
        FROM remote_work
        WHERE user_id = ?
          AND DATE(start_dt) = CURDATE()
          AND stop_dt IS NULL
        LIMIT 1
        FOR UPDATE
    ", 'i', array((int)$userID));

    if (!$openResult) {
        $transaction->rollback();
        return false;
    }

    if (mysqli_num_rows($openResult) > 0) {
        $transaction->rollback();
        return remote_work_result('error', 'Вы уже начали удалённую работу сегодня');
    }

    $created = db_execute($link, "
        INSERT INTO remote_work (user_id, supervisor_id, start_dt)
        VALUES (?, ?, NOW())
    ", 'ii', array((int)$userID, (int)$supervisorID));

    if (!$created) {
        $transaction->rollback();
        return false;
    }

    if (!$transaction->commit()) {
        return false;
    }

    return remote_work_result('success');
}

function get_remote_work_supervisors($link, $userID)
{
    $result = db_query($link, "
        SELECT DISTINCT
          g.SUPERVISORID AS id,
          CONCAT_WS(' ', e.surname, e.firstname, e.lastname) AS fio
        FROM GROUPS g
        JOIN employees e ON g.SUPERVISORID = e.id
        WHERE TRIM(g.TYPE) = '3'
          AND g.USERID = ?
        ORDER BY fio
    ", 'i', array((int)$userID));

    if (!$result) {
        return false;
    }

    $supervisors = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $supervisors[] = $row;
    }

    return $supervisors;
}

function get_open_remote_work($link, $userID)
{
    $result = db_query($link, "
        SELECT
          rw.id,
          rw.supervisor_id,
          CONCAT_WS(' ', e.surname, e.firstname, e.lastname) AS supervisor_fio
        FROM remote_work rw
        LEFT JOIN employees e ON rw.supervisor_id = e.id
        WHERE rw.user_id = ?
          AND DATE(rw.start_dt) = CURDATE()
          AND rw.stop_dt IS NULL
        ORDER BY rw.id DESC
        LIMIT 1
    ", 'i', array((int)$userID));

    if (!$result) {
        return false;
    }

    $row = mysqli_fetch_assoc($result);

    return $row ?: null;
}
