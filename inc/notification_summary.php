<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/time_journal_repository.php';

function get_supervisor_notification_counts($link, $supervisorID, $currentDateTime)
{
    $depthResult = db_query($link, "
        SELECT paramName, valueInt
        FROM DBSETUP
        WHERE paramName IN ('add_time_journal_deep_day', 'delay_journal_deep_day')
    ");

    if (!$depthResult) {
        return false;
    }

    $depthDays = array(
        'add_time_journal_deep_day' => 180,
        'delay_journal_deep_day' => 180,
    );

    while ($row = db_fetch_one($depthResult)) {
        $paramName = (string)$row['paramName'];

        if (array_key_exists($paramName, $depthDays)) {
            $depthDays[$paramName] = abs((int)$row['valueInt']);
        }
    }

    $currentDate = substr((string)$currentDateTime, 0, 10);
    list($delayQuarterStartDate, , $delayQuarterStopExclusive) = get_current_quarter_date_range(
        false,
        $currentDate
    );
    $dateTimeExpressions = time_journal_add_work_datetime_expressions($link);
    $startExpression = $dateTimeExpressions['start'];
    $stopExpression = $dateTimeExpressions['stop'];
    $countResult = db_query($link, "
        SELECT
          (
            SELECT COUNT(DISTINCT a.ID)
            FROM ADD_TIME a
            WHERE a.APPROVED = 0
              AND a.PAUSE_MODE = 0
              AND $startExpression <> '0000-00-00 00:00:00'
              AND $stopExpression <> '0000-00-00 00:00:00'
              AND $stopExpression > $startExpression
              AND $stopExpression > ADDDATE(?, INTERVAL ? DAY)
              AND EXISTS (
                SELECT 1
                FROM GROUPS membership
                WHERE membership.USERID = a.USERID
                  AND membership.SUPERVISORID = ?
                  AND TRIM(membership.TYPE) = ?
              )
          ) AS ADD_TIME_COUNT,
          (
            SELECT COUNT(DISTINCT delay_entry.id)
            FROM Delays delay_entry
            WHERE delay_entry.status = 0
              AND delay_entry.date >= ?
              AND delay_entry.date < ?
              AND EXISTS (
                SELECT 1
                FROM GROUPS membership
                WHERE membership.USERID = delay_entry.userID
                  AND membership.SUPERVISORID = ?
                  AND TRIM(membership.TYPE) IN ('0', '-1', '3')
              )
              AND EXISTS (
                SELECT 1
                FROM visiting visit
                WHERE visit.user_id = delay_entry.userID
                  AND visit.in_dt >= delay_entry.date
                  AND visit.in_dt < ADDDATE(delay_entry.date, INTERVAL 1 DAY)
                  AND visit.remoteWorkState = 0
              )
          ) AS DELAY_COUNT
    ", 'siisssi', array(
        $currentDateTime,
        -$depthDays['add_time_journal_deep_day'],
        (int)$supervisorID,
        '0',
        $delayQuarterStartDate,
        $delayQuarterStopExclusive,
        (int)$supervisorID,
    ));

    if (!$countResult) {
        return false;
    }

    $counts = db_fetch_one($countResult);

    if (!$counts) {
        return false;
    }

    return array(
        'add_time_count' => (int)$counts['ADD_TIME_COUNT'],
        'delay_count' => (int)$counts['DELAY_COUNT'],
    );
}

function get_delay_notification_summary($link, $supervisorID, $currentDate)
{
    list($quarterStartDate, $quarterStopDate, $quarterStopExclusive) = get_current_quarter_date_range(
        false,
        $currentDate
    );

    $summaryResult = db_query($link, "
        SELECT
          employee.ID AS USERID,
          CONCAT_WS(' ', employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME) AS USER_NAME,
          COUNT(DISTINCT CASE WHEN visit.ID IS NOT NULL THEN delay_entry.id END) AS TOTAL_COUNT,
          COUNT(DISTINCT CASE WHEN visit.ID IS NOT NULL AND delay_entry.status = 1 THEN delay_entry.id END) AS ACCEPTED_COUNT,
          COUNT(DISTINCT CASE WHEN visit.ID IS NOT NULL AND delay_entry.status = -1 THEN delay_entry.id END) AS REFUSED_COUNT,
          COUNT(DISTINCT CASE WHEN visit.ID IS NOT NULL AND delay_entry.status IN (99, 100, 101) THEN delay_entry.id END) AS DELETED_COUNT,
          COUNT(DISTINCT CASE WHEN visit.ID IS NOT NULL AND delay_entry.status = 0 THEN delay_entry.id END) AS NEW_COUNT,
          COUNT(DISTINCT CASE
            WHEN visit.ID IS NOT NULL
             AND delay_entry.status = 0
             AND (
               TRIM(COALESCE(delay_entry.explaneDesk, '')) = ''
               OR TRIM(delay_entry.explaneDesk) = 'Без объяснения'
             )
            THEN delay_entry.id
          END) AS WITHOUT_COMMENT_COUNT
        FROM GROUPS membership
        INNER JOIN employees employee ON employee.ID = membership.USERID
        LEFT JOIN Delays delay_entry
          ON delay_entry.userID = employee.ID
         AND delay_entry.date >= ?
         AND delay_entry.date < ?
        LEFT JOIN visiting visit
          ON visit.user_id = delay_entry.userID
         AND visit.in_dt >= delay_entry.date
         AND visit.in_dt < ADDDATE(delay_entry.date, INTERVAL 1 DAY)
         AND visit.remoteWorkState = 0
        WHERE membership.SUPERVISORID = ?
          AND TRIM(membership.TYPE) IN ('0', '-1', '3')
        GROUP BY employee.ID, employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME
        ORDER BY employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME, employee.ID
    ", 'ssi', array($quarterStartDate, $quarterStopExclusive, (int)$supervisorID));

    if (!$summaryResult) {
        return false;
    }

    $entries = array();

    while ($row = db_fetch_one($summaryResult)) {
        $entries[] = array(
            'user_id' => (int)$row['USERID'],
            'user_name' => trim((string)$row['USER_NAME']),
            'total_count' => (int)$row['TOTAL_COUNT'],
            'accepted_count' => (int)$row['ACCEPTED_COUNT'],
            'refused_count' => (int)$row['REFUSED_COUNT'],
            'deleted_count' => (int)$row['DELETED_COUNT'],
            'new_count' => (int)$row['NEW_COUNT'],
            'without_comment_count' => (int)$row['WITHOUT_COMMENT_COUNT'],
        );
    }

    return array(
        'quarter_start_date' => $quarterStartDate,
        'quarter_stop_date' => $quarterStopDate,
        'quarter_stop_exclusive' => $quarterStopExclusive,
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
         AND DATE($stopExpression) = DATE($startExpression)
         AND $startExpression >= ?
         AND $startExpression < ?
        WHERE membership.SUPERVISORID = ?
          AND TRIM(membership.TYPE) = ?
        GROUP BY employee.ID, employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME
        ORDER BY employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME, employee.ID
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

    while ($row = db_fetch_one($summaryResult)) {
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

    while ($row = db_fetch_one($entryResult)) {
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
    list($quarterStartDate, $quarterStopDate, $quarterStopExclusive) = get_current_quarter_date_range(
        false,
        $currentDateTime
    );
    $dateTimeExpressions = time_journal_add_work_datetime_expressions($link, 'add_time');
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
         AND $startExpression < ?
         AND $stopExpression > ?
         AND $startExpression <> '0000-00-00 00:00:00'
         AND $stopExpression <> '0000-00-00 00:00:00'
         AND $stopExpression > $startExpression
        WHERE membership.SUPERVISORID = ?
          AND TRIM(membership.TYPE) = ?
        GROUP BY employee.ID, employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME
        ORDER BY employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME, employee.ID
    ", 'ssis', array($quarterStopExclusive, $quarterStartDate, (int)$supervisorID, '0'));

    if (!$summaryResult) {
        return false;
    }

    $entries = array();

    while ($row = db_fetch_one($summaryResult)) {
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
        'quarter_start_date' => $quarterStartDate,
        'quarter_stop_date' => $quarterStopDate,
        'quarter_stop_exclusive' => $quarterStopExclusive,
        'entries' => $entries,
    );
}
