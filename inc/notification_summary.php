<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/time_journal_repository.php';

function get_delay_notification_summary($link, $supervisorID, $currentDate)
{
    $depthResult = db_query($link, "
        SELECT valueInt
        FROM DBSETUP
        WHERE paramName = 'delay_journal_deep_day'
        LIMIT 1
    ");

    if (!$depthResult) {
        return false;
    }

    $depthRow = mysqli_fetch_assoc($depthResult);
    $depthDays = $depthRow ? abs((int)$depthRow['valueInt']) : 180;
    $summaryResult = db_query($link, "
        SELECT
          employee.ID AS USERID,
          CONCAT_WS(' ', employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME) AS USER_NAME,
          COUNT(DISTINCT delay_entry.id) AS TOTAL_COUNT,
          COUNT(DISTINCT CASE WHEN delay_entry.status = 1 THEN delay_entry.id END) AS ACCEPTED_COUNT,
          COUNT(DISTINCT CASE WHEN delay_entry.status = -1 THEN delay_entry.id END) AS REFUSED_COUNT,
          COUNT(DISTINCT CASE WHEN delay_entry.status IN (99, 100, 101) THEN delay_entry.id END) AS DELETED_COUNT,
          COUNT(DISTINCT CASE WHEN delay_entry.status = 0 THEN delay_entry.id END) AS NEW_COUNT
        FROM GROUPS membership
        INNER JOIN employees employee ON employee.ID = membership.USERID
        LEFT JOIN Delays delay_entry
          ON delay_entry.userID = employee.ID
         AND delay_entry.date > ADDDATE(?, INTERVAL ? DAY)
         AND EXISTS (
           SELECT 1
           FROM visiting visit
           WHERE visit.user_id = delay_entry.userID
             AND visit.in_dt >= delay_entry.date
             AND visit.in_dt < ADDDATE(delay_entry.date, INTERVAL 1 DAY)
             AND visit.remoteWorkState = 0
         )
        WHERE membership.SUPERVISORID = ?
          AND TRIM(membership.TYPE) = ?
        GROUP BY employee.ID, employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME
        ORDER BY employee.ID
    ", 'siis', array($currentDate, -$depthDays, (int)$supervisorID, '3'));

    if (!$summaryResult) {
        return false;
    }

    $entries = array();

    while ($row = mysqli_fetch_assoc($summaryResult)) {
        $entries[] = array(
            'user_id' => (int)$row['USERID'],
            'user_name' => trim((string)$row['USER_NAME']),
            'total_count' => (int)$row['TOTAL_COUNT'],
            'accepted_count' => (int)$row['ACCEPTED_COUNT'],
            'refused_count' => (int)$row['REFUSED_COUNT'],
            'deleted_count' => (int)$row['DELETED_COUNT'],
            'new_count' => (int)$row['NEW_COUNT'],
        );
    }

    $rangeStart = date('d.m.Y', strtotime($currentDate . ' -' . $depthDays . ' days'));
    $rangeStop = date('d-m-Y', strtotime($currentDate));

    return array(
        'depth_days' => $depthDays,
        'range_start_label' => $rangeStart,
        'range_stop_label' => $rangeStop,
        'entries' => $entries,
    );
}

function get_pause_notification_summary($link, $supervisorID, $currentDateTime)
{
    list($quarterStartDate, $quarterStopDate, $quarterStopExclusive) = get_current_quarter_date_range(
        false,
        $currentDateTime
    );
    $currentDate = substr((string)$currentDateTime, 0, 10);
    $dateTimeExpressions = time_journal_add_work_datetime_expressions($link);
    $startExpression = $dateTimeExpressions['start'];
    $stopExpression = $dateTimeExpressions['stop'];
    $summaryResult = db_query($link, "
        SELECT
          employee.ID AS USERID,
          CONCAT_WS(' ', employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME) AS USER_NAME,
          COUNT(DISTINCT a.ID) AS TOTAL_COUNT,
          COUNT(DISTINCT CASE WHEN DATE($startExpression) = ? THEN a.ID END) AS CURRENT_DAY_COUNT
        FROM GROUPS membership
        INNER JOIN employees employee ON employee.ID = membership.USERID
        LEFT JOIN ADD_TIME a
          ON a.USERID = employee.ID
         AND a.PAUSE_MODE = 1
         AND $startExpression <> '0000-00-00 00:00:00'
         AND $stopExpression <> '0000-00-00 00:00:00'
         AND $stopExpression > $startExpression
         AND $startExpression >= ?
         AND $startExpression < ?
        WHERE membership.SUPERVISORID = ?
          AND TRIM(membership.TYPE) = ?
        GROUP BY employee.ID, employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME
        ORDER BY employee.ID
    ", 'sssis', array(
        $currentDate,
        $quarterStartDate,
        $quarterStopExclusive,
        (int)$supervisorID,
        '4',
    ));

    if (!$summaryResult) {
        return false;
    }

    $entries = array();

    while ($row = mysqli_fetch_assoc($summaryResult)) {
        $entries[] = array(
            'user_id' => (int)$row['USERID'],
            'user_name' => trim((string)$row['USER_NAME']),
            'total_count' => (int)$row['TOTAL_COUNT'],
            'current_day_count' => (int)$row['CURRENT_DAY_COUNT'],
        );
    }

    return array(
        'quarter_start_date' => $quarterStartDate,
        'quarter_stop_date' => $quarterStopDate,
        'quarter_stop_exclusive' => $quarterStopExclusive,
        'entries' => $entries,
    );
}

function get_pause_notification_count($link, $userID, $currentDateTime)
{
    list($quarterStartDate, , $quarterStopExclusive) = get_current_quarter_date_range(
        false,
        $currentDateTime
    );
    $currentDate = substr((string)$currentDateTime, 0, 10);
    $dateTimeExpressions = time_journal_add_work_datetime_expressions($link);
    $entryResult = time_journal_query_pause_intervals(
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

    $totalCount = 0;
    $currentDayCount = 0;

    while ($row = mysqli_fetch_assoc($entryResult)) {
        $totalCount++;

        if (substr((string)$row['START_DT_EFFECTIVE'], 0, 10) === $currentDate) {
            $currentDayCount++;
        }
    }

    return array(
        'total_count' => $totalCount,
        'current_day_count' => $currentDayCount,
    );
}

function get_add_time_notification_summary($link, $supervisorID, $currentDateTime)
{
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
    $startExpression = $dateTimeExpressions['start'];
    $stopExpression = $dateTimeExpressions['stop'];
    $summaryResult = db_query($link, "
        SELECT
          employee.ID AS USERID,
          CONCAT_WS(' ', employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME) AS USER_NAME,
          COUNT(DISTINCT add_time.ID) AS TOTAL_COUNT,
          COUNT(DISTINCT CASE WHEN add_time.APPROVED = 1 THEN add_time.ID END) AS ACCEPTED_COUNT,
          COUNT(DISTINCT CASE WHEN add_time.APPROVED = -1 THEN add_time.ID END) AS REFUSED_COUNT,
          COUNT(DISTINCT CASE WHEN add_time.APPROVED IN (99, 100, 101) THEN add_time.ID END) AS DELETED_COUNT,
          COUNT(DISTINCT CASE WHEN add_time.APPROVED = 0 THEN add_time.ID END) AS NEW_COUNT
        FROM GROUPS membership
        INNER JOIN employees employee ON employee.ID = membership.USERID
        LEFT JOIN ADD_TIME add_time
          ON add_time.USERID = employee.ID
         AND add_time.PAUSE_MODE = 0
         AND $stopExpression > ADDDATE(?, INTERVAL ? DAY)
         AND $startExpression <> '0000-00-00 00:00:00'
         AND $stopExpression <> '0000-00-00 00:00:00'
         AND $stopExpression > $startExpression
        WHERE membership.SUPERVISORID = ?
          AND TRIM(membership.TYPE) = ?
        GROUP BY employee.ID, employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME
        ORDER BY employee.ID
    ", 'siis', array($currentDateTime, -$depthDays, (int)$supervisorID, '0'));

    if (!$summaryResult) {
        return false;
    }

    $entries = array();

    while ($row = mysqli_fetch_assoc($summaryResult)) {
        $entries[] = array(
            'user_id' => (int)$row['USERID'],
            'user_name' => trim((string)$row['USER_NAME']),
            'total_count' => (int)$row['TOTAL_COUNT'],
            'accepted_count' => (int)$row['ACCEPTED_COUNT'],
            'refused_count' => (int)$row['REFUSED_COUNT'],
            'deleted_count' => (int)$row['DELETED_COUNT'],
            'new_count' => (int)$row['NEW_COUNT'],
        );
    }

    return array(
        'depth_days' => $depthDays,
        'entries' => $entries,
    );
}
