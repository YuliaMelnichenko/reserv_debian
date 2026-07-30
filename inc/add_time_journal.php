<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/time_journal_repository.php';

function get_add_time_journal_context($link, $userID, $currentDateTime, $includeDeleted = true)
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

    $user = db_fetch_one($userResult);

    if (!$user) {
        return null;
    }

    list($quarterStartDate, $quarterStopDate, $quarterStopExclusive) = get_current_quarter_date_range(
        false,
        $currentDateTime
    );
    $dateTimeExpressions = time_journal_add_work_datetime_expressions($link);
    $entryResult = time_journal_query_add_work_journal(
        $link,
        (int)$userID,
        0,
        $quarterStartDate,
        $quarterStopExclusive,
        $dateTimeExpressions['start'],
        $dateTimeExpressions['stop']
    );

    if (!$entryResult) {
        $legacyQueryError = db_error($link);
        error_log(
            '[TORI] Add-time journal compatibility query failed; retrying modern columns: '
            . $legacyQueryError
        );
        $entryResult = time_journal_query_add_work_journal(
            $link,
            (int)$userID,
            0,
            $quarterStartDate,
            $quarterStopExclusive,
            'a.START_DT',
            'a.STOP_DT'
        );
    }

    if (!$entryResult) {
        error_log('[TORI] Add-time journal query failed: ' . db_error($link));
        return false;
    }

    $entries = array();

    while ($row = db_fetch_one($entryResult)) {
        $status = (int)$row['APPROVED'];

        if (!$includeDeleted && in_array($status, array(99, 100, 101), true)) {
            continue;
        }

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
            'status' => $status,
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
