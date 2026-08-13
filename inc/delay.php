<?php

function is_delay_check_disabled_for_weekend($dateTime)
{
    return is_delay_check_disabled_for_calendar_day($dateTime);
}

function is_delay_check_disabled_for_calendar_day($dateTime, $workdayOverride = null)
{
    if (!is_string($dateTime) || trim($dateTime) === '') {
        return false;
    }

    $timestamp = strtotime($dateTime);

    if ($timestamp === false) {
        return false;
    }

    $isWorkday = (int)date('N', $timestamp) <= 5;

    if ($workdayOverride !== null) {
        $isWorkday = in_array((int)$workdayOverride, array(1, 2), true);
    }

    return !$isWorkday;
}

function is_delay_check_disabled_for_non_working_day($link, $userID, $dateTime)
{
    if (!is_string($dateTime) || trim($dateTime) === '') {
        return false;
    }

    $timestamp = strtotime($dateTime);

    if ($timestamp === false) {
        return false;
    }

    $date = date('Y-m-d', $timestamp);
    $calendarResult = db_query(
        $link,
        'SELECT type FROM work_dayoff WHERE date = ? LIMIT 1',
        's',
        array($date)
    );
    $calendarRow = db_fetch_one($calendarResult);
    $calendarOverride = $calendarRow ? (int)$calendarRow['type'] : null;

    if (is_delay_check_disabled_for_calendar_day($date, $calendarOverride)) {
        return true;
    }

    $leaveResult = db_query(
        $link,
        "SELECT 1
         FROM staff_leaves
         WHERE user_id = ?
           AND event IN ('Отпуск', 'Больничный', 'Командировка')
           AND start_date <= ?
           AND stop_date >= ?
         LIMIT 1",
        'iss',
        array((int)$userID, $date, $date)
    );

    return $leaveResult && db_has_rows($leaveResult);
}

function get_delay_value($arrivalDateTime, $defaultStartTime, $allowedDelay)
{
    if (
        !is_string($arrivalDateTime)
        || !is_string($defaultStartTime)
        || trim($arrivalDateTime) === ''
        || trim($defaultStartTime) === ''
        || $defaultStartTime === 'NDF'
        || $arrivalDateTime === '0000-00-00 00:00:00'
    ) {
        return array(0, 0);
    }

    $arrivalTimestamp = strtotime($arrivalDateTime);

    if ($arrivalTimestamp === false) {
        return array(0, 0);
    }

    if (is_delay_check_disabled_for_weekend($arrivalDateTime)) {
        return array(0, 0);
    }

    if (strlen($defaultStartTime) === 5) {
        $defaultStartTime .= ':00';
    }

    $arrivalDate = date('Y-m-d', $arrivalTimestamp);
    $startTimestamp = strtotime($arrivalDate . ' ' . $defaultStartTime);

    if ($startTimestamp === false) {
        return array(0, 0);
    }

    $allowedDelay = max(0, (int)$allowedDelay);
    $allowedArrivalTimestamp = $startTimestamp + ($allowedDelay * 60);

    if ($arrivalTimestamp <= $allowedArrivalTimestamp) {
        return array(0, 0);
    }

    return array(1, $arrivalTimestamp - $allowedArrivalTimestamp);
}
