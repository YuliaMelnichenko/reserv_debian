<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/time_journal_repository.php';

function get_add_time_journal_context($link, $userID, $currentDateTime)
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

    $depthResult = db_query($link, "
        SELECT valueInt
        FROM DBSETUP
        WHERE paramName = 'add_time_journal_deep_day'
        LIMIT 1
    ");

    if (!$depthResult) {
        return false;
    }

    $depthRow = mysqli_fetch_assoc($depthResult);
    $depthDays = $depthRow ? abs((int)$depthRow['valueInt']) : 180;
    $dateTimeExpressions = time_journal_add_work_datetime_expressions($link);
    $entryResult = time_journal_query_add_work_journal(
        $link,
        (int)$userID,
        0,
        $currentDateTime,
        -$depthDays,
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
        $duration = $startTimestamp !== false && $stopTimestamp !== false && $stopTimestamp > $startTimestamp
            ? $stopTimestamp - $startTimestamp
            : 0;

        $entries[] = array(
            'id' => (int)$row['ID'],
            'start_datetime' => $startDateTime,
            'stop_datetime' => $stopDateTime,
            'duration' => $duration,
            'reason_id' => (int)$row['REASON'],
            'reason_description' => (string)$row['REASONDESCRIPTION'],
            'employee_comment' => (string)$row['DESCRIPTION'],
            'supervisor_id' => (int)$row['SUIR'],
            'supervisor_name' => trim((string)$row['SUPERVISOR_NAME']),
            'decision_comment' => (string)$row['SUPERVISORDESC'],
            'status' => (int)$row['APPROVED'],
        );
    }

    return array(
        'user_name' => trim($user['SURNAME'] . ' ' . $user['FIRSTNAME'] . ' ' . $user['LASTNAME']),
        'entries' => $entries,
    );
}
