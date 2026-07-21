<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/time_journal_repository.php';

function get_pause_journal_context($link, $userID, $currentDateTime)
{
    $userResult = db_query($link, "
        SELECT SURNAME, FIRSTNAME, LASTNAME
        FROM employees
        WHERE ID = ?
        LIMIT 1
    ", 'i', array((int)$userID));

    if (!$userResult) {
        return false;
    }

    $user = mysqli_fetch_assoc($userResult);

    if (!$user) {
        return null;
    }

    list($quarterStartDate, $quarterStopDate, $quarterStopExclusive) = get_current_quarter_date_range(false, $currentDateTime);
    $dateTimeExpressions = time_journal_add_work_datetime_expressions($link);
    $entryResult = time_journal_query_pause_journal(
        $link,
        (int)$userID,
        $quarterStartDate,
        $quarterStopExclusive,
        $dateTimeExpressions['start'],
        $dateTimeExpressions['stop']
    );

    if (!$entryResult) {
        return false;
    }

    $entries = array();

    while ($row = mysqli_fetch_assoc($entryResult)) {
        $startDateTime = (string)$row['START_DT_EFFECTIVE'];
        $stopDateTime = (string)$row['STOP_DT_EFFECTIVE'];
        $startTimestamp = strtotime($startDateTime);
        $stopTimestamp = strtotime($stopDateTime);

        if ($startTimestamp === false || $stopTimestamp === false || $stopTimestamp <= $startTimestamp) {
            continue;
        }

        $entries[] = array(
            'id' => (int)$row['ID'],
            'date' => date('Y-m-d', $startTimestamp),
            'start_datetime' => $startDateTime,
            'stop_datetime' => $stopDateTime,
            'duration' => $stopTimestamp - $startTimestamp,
            'employee_comment' => (string)$row['DESCRIPTION'],
            'supervisor_id' => (int)$row['SUIR'],
            'supervisor_name' => trim((string)$row['SUPERVISOR_NAME']),
        );
    }

    return array(
        'user_name' => trim($user['SURNAME'] . ' ' . $user['FIRSTNAME'] . ' ' . $user['LASTNAME']),
        'quarter_start_date' => $quarterStartDate,
        'quarter_stop_date' => $quarterStopDate,
        'quarter_stop_exclusive' => $quarterStopExclusive,
        'entries' => $entries,
    );
}
