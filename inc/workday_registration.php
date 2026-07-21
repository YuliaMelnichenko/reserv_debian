<?php

require_once __DIR__ . '/database.php';

function reset_time_registration_session()
{
    $_SESSION['ss_state'] = 1;
    $_SESSION['ss_visiting_ID'] = 0;
}

function is_workday_visit_current($visitRow, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds)
{
    if (!is_array($visitRow) || !isset($visitRow['in_dt'], $visitRow['state'])) {
        return false;
    }

    $inTime = strtotime($visitRow['in_dt']);
    $nowTime = strtotime($dateTimeStr);

    if ($inTime === false || $nowTime === false) {
        return false;
    }

    $duration = $nowTime - $inTime;

    if ($duration < 0) {
        return false;
    }

    $isInCurrentPeriod = (
        $visitRow['in_dt'] >= $startDTStr
        && $visitRow['in_dt'] < $stopDTStr
    );
    $isAllowedOvernightOpenShift = (
        (int)$visitRow['state'] !== 0
        && $duration <= (int)$maxOpenShiftSeconds
    );

    return $isInCurrentPeriod || $isAllowedOvernightOpenShift;
}

function sync_time_registration_state_from_db($link, $userID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds)
{
    $query = db_query($link, "
        SELECT ID, state
        FROM visiting
        WHERE user_id = ?
          AND (
            (
              in_dt >= ?
              AND in_dt < ?
            )
            OR
            (
              state != 0
              AND in_dt < ?
              AND TIMESTAMPDIFF(SECOND, ?, ?) <= ?
            )
          )
        ORDER BY in_dt DESC, ID DESC
        LIMIT 1
    ", 'isssssi', array((int)$userID, $startDTStr, $stopDTStr, $startDTStr, $startDTStr, $dateTimeStr, (int)$maxOpenShiftSeconds));

    if (!$query) {
        ajax_database_error($link, __FILE__ . ':' . __LINE__);
        exit;
    }

    if (mysqli_num_rows($query) === 0) {
        reset_time_registration_session();

        return array(
            'state' => 1,
            'visiting_ID' => 0,
        );
    }

    $row = mysqli_fetch_array($query, MYSQLI_ASSOC);

    $_SESSION['ss_state'] = (int)$row['state'];
    $_SESSION['ss_visiting_ID'] = (int)$row['ID'];

    return array(
        'state' => (int)$row['state'],
        'visiting_ID' => (int)$row['ID'],
    );
}

function get_current_visit_row($link, $userID, $visitID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds)
{
    if ((int)$visitID <= 0) {
        return null;
    }

    $query = db_query($link, "
        SELECT ID, user_id, in_dt, eat_start_dt, eat_stop_dt, out_dt, state
        FROM visiting
        WHERE ID = ?
          AND user_id = ?
        LIMIT 1
    ", 'ii', array((int)$visitID, (int)$userID));

    if (!$query) {
        ajax_database_error($link, __FILE__ . ':' . __LINE__);
        exit;
    }

    if (mysqli_num_rows($query) === 0) {
        return null;
    }

    $visitRow = mysqli_fetch_array($query, MYSQLI_ASSOC);

    if (!is_workday_visit_current($visitRow, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds)) {
        return null;
    }

    return $visitRow;
}

function require_current_visit_row($link, $userID, $visitID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, $expectedState)
{
    $visitRow = get_current_visit_row(
        $link,
        $userID,
        $visitID,
        $startDTStr,
        $stopDTStr,
        $dateTimeStr,
        $maxOpenShiftSeconds
    );

    if ($visitRow === null) {
        reset_time_registration_session();
        echo 'Ошибка: текущая запись рабочего дня устарела или не найдена. Обновите страницу и начните регистрацию заново.';
        exit;
    }

    if ((int)$visitRow['state'] !== (int)$expectedState) {
        $_SESSION['ss_state'] = (int)$visitRow['state'];
        $_SESSION['ss_visiting_ID'] = (int)$visitRow['ID'];

        echo 'Ошибка: состояние рабочего дня уже изменилось. Обновите страницу.';
        exit;
    }

    return $visitRow;
}
