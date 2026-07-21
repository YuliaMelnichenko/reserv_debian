<?php

require_once __DIR__ . '/database.php';

function delete_past_gym_schedule($link, $userID)
{
    return db_execute(
        $link,
        'DELETE FROM gym_schedule WHERE USERID = ? AND DATE_TRAIN < CURDATE()',
        'i',
        array((int)$userID)
    );
}

function get_active_gym_visitors($link)
{
    $result = db_query($link, "
        SELECT
          a.USERID,
          a.START_DT,
          e.firstname,
          e.lastname,
          e.surname
        FROM ADD_TIME a
        INNER JOIN employees e ON e.id = a.USERID
        WHERE a.DESCRIPTION = ?
          AND a.STOP_DT = ?
          AND a.START_DT <> '0000-00-00 00:00:00'
        ORDER BY a.START_DT, e.surname, e.firstname, e.lastname
    ", 'ss', array('Посещение тренажерного зала', '0000-00-00 00:00:00'));

    if (!$result) {
        return false;
    }

    $visitors = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $visitors[] = array(
            'user_id' => (int)$row['USERID'],
            'full_name' => trim($row['surname'] . ' ' . $row['firstname'] . ' ' . $row['lastname']),
            'start_time' => date('H:i', strtotime($row['START_DT'])),
        );
    }

    return $visitors;
}

function user_has_gym_schedule($link, $userID)
{
    $result = db_query(
        $link,
        'SELECT 1 FROM gym_schedule WHERE USERID = ? LIMIT 1',
        'i',
        array((int)$userID)
    );

    if (!$result) {
        return null;
    }

    return mysqli_num_rows($result) > 0;
}

function format_gym_schedule_date($dateValue)
{
    $parts = preg_split('/\s+/', trim((string)$dateValue));
    $months = array(
        '01' => 'января',
        '02' => 'февраля',
        '03' => 'марта',
        '04' => 'апреля',
        '05' => 'мая',
        '06' => 'июня',
        '07' => 'июля',
        '08' => 'августа',
        '09' => 'сентября',
        '10' => 'октября',
        '11' => 'ноября',
        '12' => 'декабря',
    );

    if (count($parts) !== 2 || !isset($months[$parts[1]])) {
        return trim((string)$dateValue);
    }

    return $parts[0] . ' ' . $months[$parts[1]];
}

function get_upcoming_gym_schedule($link)
{
    $result = db_query($link, "
        SELECT
          gs.USERID,
          e.firstname,
          e.lastname,
          e.surname,
          GROUP_CONCAT(
            DATE_FORMAT(gs.DATE_TRAIN, '%d %m')
            ORDER BY gs.DATE_TRAIN, gs.START_TIME
            SEPARATOR '|'
          ) AS DATE_VALUES,
          GROUP_CONCAT(
            CONCAT(TIME_FORMAT(gs.START_TIME, '%H:%i'), '-', TIME_FORMAT(gs.STOP_TIME, '%H:%i'))
            ORDER BY gs.DATE_TRAIN, gs.START_TIME
            SEPARATOR '|'
          ) AS TIME_VALUES,
          MIN(gs.DATE_TRAIN) AS FIRST_DATE
        FROM gym_schedule gs
        INNER JOIN employees e ON e.id = gs.USERID
        WHERE gs.DATE_TRAIN >= CURDATE()
        GROUP BY gs.USERID, e.firstname, e.lastname, e.surname
        ORDER BY FIRST_DATE, e.surname, e.firstname, e.lastname
    ");

    if (!$result) {
        return false;
    }

    $schedule = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $dateValues = is_string($row['DATE_VALUES']) && $row['DATE_VALUES'] !== ''
            ? explode('|', $row['DATE_VALUES'])
            : array();
        $timeValues = is_string($row['TIME_VALUES']) && $row['TIME_VALUES'] !== ''
            ? explode('|', $row['TIME_VALUES'])
            : array();

        $schedule[] = array(
            'user_id' => (int)$row['USERID'],
            'full_name' => trim($row['surname'] . ' ' . $row['firstname'] . ' ' . $row['lastname']),
            'dates' => array_map('format_gym_schedule_date', $dateValues),
            'times' => $timeValues,
        );
    }

    return $schedule;
}
