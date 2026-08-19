<?php

require_once __DIR__ . '/date_range.php';
require_once __DIR__ . '/calendar.php';

function get_accounting_error_status_name($status)
{
    $names = array(
        0 => 'Нет данных',
        1 => 'На рассмотрении',
        2 => 'Принято',
        3 => 'Отклонено',
        4 => 'Удалено',
    );

    return isset($names[(int)$status]) ? $names[(int)$status] : 'Неизвестно';
}

function get_accounting_errors_default_depth_days()
{
    return 0;
}

function is_accounting_errors_exempt_user($userID)
{
    return in_array((int)$userID, array(156, 161, 600), true);
}

function accounting_errors_log_database_failure($link, $context)
{
    database_error_message($link, $context);
    return false;
}

function accounting_errors_get_range($depthDays = 0)
{
    return get_current_quarter_date_range(true);
}

function get_accounting_errors_period_label()
{
    list($startDate, $stopDate) = accounting_errors_get_range();
    return format_date_range_label($startDate, $stopDate);
}

function accounting_errors_remove_dates_from_result($result, $column, &$dates)
{
    while ($row = db_fetch_one($result)) {
        if (!empty($row[$column])) {
            unset($dates[$row[$column]]);
        }
    }
}

function clear_unsubmitted_accounting_errors_for_dates($link, $userID, $dates)
{
    $normalizedDates = array();

    foreach ($dates as $date) {
        $normalizedDate = normalize_date_value($date);

        if ($normalizedDate !== null) {
            $normalizedDates[$normalizedDate] = true;
        }
    }

    foreach (array_keys($normalizedDates) as $date) {
        $deleted = db_execute(
            $link,
            'DELETE FROM accounting_errors WHERE USERID = ? AND ERROR_DATE = ? AND STATUS = 0',
            'is',
            array((int)$userID, $date)
        );

        if (!$deleted) {
            return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        }
    }

    return true;
}

function clear_business_trip_missing_data_for_dates($link, $userID, $dates)
{
    $normalizedDates = array();

    foreach ($dates as $date) {
        $normalizedDate = normalize_date_value($date);

        if ($normalizedDate !== null) {
            $normalizedDates[$normalizedDate] = true;
        }
    }

    foreach (array_keys($normalizedDates) as $date) {
        $deleted = db_execute(
            $link,
            'DELETE FROM business_trip_missing_data WHERE USERID = ? AND TRIP_DATE = ?',
            'is',
            array((int)$userID, $date)
        );

        if (!$deleted) {
            return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        }
    }

    return true;
}

function sync_business_trip_missing_data_for_user($link, $userID, $depthDays = 0)
{
    $userID = (int)$userID;

    if ($userID <= 0 || is_accounting_errors_exempt_user($userID)) {
        return 0;
    }

    list($startDate, $stopDate, $stopExclusive) = accounting_errors_get_range($depthDays);
    $tripDates = array();
    $tripResult = db_query(
        $link,
        "SELECT start_date, stop_date
         FROM staff_leaves
         WHERE user_id = ?
           AND event = 'Командировка'
           AND start_date <= ?
           AND stop_date >= ?",
        'iss',
        array($userID, $stopDate, $startDate)
    );

    if (!$tripResult) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    while ($trip = db_fetch_one($tripResult)) {
        foreach (get_days_range_inclusive(
            max($startDate, (string)$trip['start_date']),
            min($stopDate, (string)$trip['stop_date'])
        ) as $tripDate) {
            $tripDates[$tripDate] = true;
        }
    }

    $coveredDates = array();
    $offsiteResult = db_query(
        $link,
        "SELECT START_DT, STOP_DT
         FROM ADD_TIME
         WHERE USERID = ?
           AND PAUSE_MODE = 0
           AND START_DT IS NOT NULL
           AND STOP_DT IS NOT NULL
           AND START_DT <> '0000-00-00 00:00:00'
           AND STOP_DT <> '0000-00-00 00:00:00'
           AND STOP_DT > START_DT
           AND START_DT < ?
           AND STOP_DT > ?",
        'iss',
        array($userID, $stopExclusive, $startDate . ' 00:00:00')
    );

    if (!$offsiteResult) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    $periodStartTimestamp = strtotime($startDate . ' 00:00:00');
    $periodStopTimestamp = strtotime($stopExclusive . ' 00:00:00');

    while ($entry = db_fetch_one($offsiteResult)) {
        $entryStart = strtotime((string)$entry['START_DT']);
        $entryStop = strtotime((string)$entry['STOP_DT']);

        if ($entryStart === false || $entryStop === false || $entryStop <= $entryStart) {
            continue;
        }

        $segmentStart = max($entryStart, $periodStartTimestamp);
        $segmentStop = min($entryStop, $periodStopTimestamp);

        while ($segmentStart < $segmentStop) {
            $coveredDates[date('Y-m-d', $segmentStart)] = true;
            $nextDateStart = strtotime(date('Y-m-d 00:00:00', $segmentStart) . ' +1 day');
            $segmentStart = min($nextDateStart, $segmentStop);
        }
    }

    $missingDates = array_diff_key($tripDates, $coveredDates);
    $transaction = db_transaction_start($link);

    if (!$transaction) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    $existingResult = db_query(
        $link,
        'SELECT ID, TRIP_DATE FROM business_trip_missing_data WHERE USERID = ? AND TRIP_DATE >= ? AND TRIP_DATE <= ? FOR UPDATE',
        'iss',
        array($userID, $startDate, $stopDate)
    );

    if (!$existingResult) {
        $transaction->rollback();
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    $existingDates = array();

    while ($existing = db_fetch_one($existingResult)) {
        $tripDate = (string)$existing['TRIP_DATE'];
        $existingDates[$tripDate] = true;

        if (!isset($missingDates[$tripDate])) {
            $deleted = db_execute(
                $link,
                'DELETE FROM business_trip_missing_data WHERE ID = ? AND USERID = ?',
                'ii',
                array((int)$existing['ID'], $userID)
            );

            if (!$deleted) {
                $transaction->rollback();
                return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
            }
        }
    }

    $insertedCount = 0;

    foreach (array_keys($missingDates) as $tripDate) {
        if (isset($existingDates[$tripDate])) {
            continue;
        }

        $inserted = db_execute_affected_rows(
            $link,
            'INSERT INTO business_trip_missing_data (USERID, TRIP_DATE, CREATED_DT)
             SELECT ?, ?, NOW()
             FROM DUAL
             WHERE NOT EXISTS (
               SELECT 1 FROM business_trip_missing_data WHERE USERID = ? AND TRIP_DATE = ? LIMIT 1
             )',
            'isis',
            array($userID, $tripDate, $userID, $tripDate)
        );

        if ($inserted === false) {
            $transaction->rollback();
            return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        }

        $insertedCount += max(0, $inserted);
    }

    if (!$transaction->commit()) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    return $insertedCount;
}

function get_business_trip_missing_data_rows($link, $userID, $depthDays = 0)
{
    if (is_accounting_errors_exempt_user($userID)) {
        return array();
    }

    list($startDate, $stopDate) = accounting_errors_get_range($depthDays);
    $result = db_query(
        $link,
        'SELECT ID, TRIP_DATE FROM business_trip_missing_data WHERE USERID = ? AND TRIP_DATE >= ? AND TRIP_DATE <= ? ORDER BY TRIP_DATE DESC',
        'iss',
        array((int)$userID, $startDate, $stopDate)
    );

    if (!$result) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return false;
    }

    return db_fetch_all($result);
}

function get_business_trip_missing_data_count($link, $userID, $depthDays = 0)
{
    $rows = get_business_trip_missing_data_rows($link, $userID, $depthDays);
    return is_array($rows) ? count($rows) : 0;
}

function sync_accounting_errors_for_user($link, $userID, $depthDays = 0)
{
    $userID = (int)$userID;

    if ($userID <= 0) {
        return false;
    }

    if (is_accounting_errors_exempt_user($userID)) {
        return 0;
    }

    list($startDate, $stopDate, $stopExclusive) = accounting_errors_get_range($depthDays);
    $workdayOverrides = array();

    $calendarResult = db_query(
        $link,
        'SELECT date, type FROM work_dayoff WHERE date >= ? AND date <= ? AND type IN (0, 1, 2)',
        'ss',
        array($startDate, $stopDate)
    );

    if (!$calendarResult) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    while ($row = db_fetch_one($calendarResult)) {
        $workdayOverrides[$row['date']] = (int)$row['type'];
    }

    $missingDates = array();
    $day = new DateTimeImmutable($startDate);
    $lastDay = new DateTimeImmutable($stopDate);

    while ($day <= $lastDay) {
        $date = $day->format('Y-m-d');
        $isWorkday = (int)$day->format('N') <= 5;

        if (isset($workdayOverrides[$date])) {
            $isWorkday = in_array($workdayOverrides[$date], array(1, 2), true);
        }

        if ($isWorkday) {
            $missingDates[$date] = true;
        }

        $day = $day->modify('+1 day');
    }

    $visitingResult = db_query(
        $link,
        "SELECT DISTINCT DATE(in_dt) AS work_date
         FROM visiting
         WHERE user_id = ?
           AND in_dt >= ?
           AND in_dt < ?
           AND in_dt IS NOT NULL
           AND in_dt != '0000-00-00 00:00:00'",
        'iss',
        array($userID, $startDate, $stopExclusive)
    );

    if (!$visitingResult) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    accounting_errors_remove_dates_from_result($visitingResult, 'work_date', $missingDates);

    $addTimeResult = db_query(
        $link,
        "SELECT DISTINCT DATE(START_DT) AS work_date
         FROM ADD_TIME
         WHERE USERID = ?
           AND START_DT >= ?
           AND START_DT < ?
           AND START_DT IS NOT NULL
           AND START_DT != '0000-00-00 00:00:00'
           AND REASON IN (1, 2, 3, 4, 5)",
        'iss',
        array($userID, $startDate, $stopExclusive)
    );

    if (!$addTimeResult) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    accounting_errors_remove_dates_from_result($addTimeResult, 'work_date', $missingDates);

    $leavesResult = db_query(
        $link,
        "SELECT start_date, stop_date
         FROM staff_leaves
         WHERE user_id = ?
           AND event IN ('Отпуск', 'Больничный', 'Командировка')
           AND start_date <= ?
           AND stop_date >= ?",
        'iss',
        array($userID, $stopDate, $startDate)
    );

    if (!$leavesResult) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    while ($leave = db_fetch_one($leavesResult)) {
        $leaveStart = max($startDate, $leave['start_date']);
        $leaveStop = min($stopDate, $leave['stop_date']);
        $leaveDay = new DateTimeImmutable($leaveStart);
        $leaveLastDay = new DateTimeImmutable($leaveStop);

        while ($leaveDay <= $leaveLastDay) {
            unset($missingDates[$leaveDay->format('Y-m-d')]);
            $leaveDay = $leaveDay->modify('+1 day');
        }
    }

    $transaction = db_transaction_start($link);

    if (!$transaction) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    $existingResult = db_query(
        $link,
        'SELECT ID, ERROR_DATE, STATUS FROM accounting_errors WHERE USERID = ? AND ERROR_DATE >= ? AND ERROR_DATE <= ? FOR UPDATE',
        'iss',
        array($userID, $startDate, $stopDate)
    );

    if (!$existingResult) {
        $transaction->rollback();
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    $existingDates = array();

    while ($existing = db_fetch_one($existingResult)) {
        $errorID = (int)$existing['ID'];
        $errorDate = $existing['ERROR_DATE'];
        $status = (int)$existing['STATUS'];
        $existingDates[$errorDate] = true;

        if ($status === 0 && !isset($missingDates[$errorDate])) {
            $deleted = db_execute(
                $link,
                'DELETE FROM accounting_errors WHERE ID = ? AND USERID = ? AND STATUS = 0',
                'ii',
                array($errorID, $userID)
            );

            if (!$deleted) {
                $transaction->rollback();
                return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
            }

            unset($existingDates[$errorDate]);
        }
    }

    $insertedCount = 0;

    foreach (array_keys($missingDates) as $errorDate) {
        if (isset($existingDates[$errorDate])) {
            continue;
        }

        $inserted = db_execute_affected_rows(
            $link,
            'INSERT INTO accounting_errors (USERID, ERROR_DATE, STATUS, CREATED_DT)
             SELECT ?, ?, 0, NOW()
             FROM DUAL
             WHERE NOT EXISTS (
               SELECT 1 FROM accounting_errors WHERE USERID = ? AND ERROR_DATE = ? LIMIT 1
             )',
            'isis',
            array($userID, $errorDate, $userID, $errorDate)
        );

        if ($inserted === false) {
            $transaction->rollback();
            return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        }

        $insertedCount += max(0, $inserted);
    }

    if (!$transaction->commit()) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    $tripSyncResult = sync_business_trip_missing_data_for_user($link, $userID, $depthDays);

    if ($tripSyncResult === false) {
        return false;
    }

    return $insertedCount + $tripSyncResult;
}

function get_accounting_errors_count($link, $userID)
{
    if (is_accounting_errors_exempt_user($userID)) {
        return 0;
    }

    list($startDate, $stopDate) = accounting_errors_get_range();

    $result = db_query(
        $link,
        'SELECT COUNT(*) AS CNT FROM accounting_errors WHERE USERID = ? AND ERROR_DATE >= ? AND ERROR_DATE <= ? AND STATUS IN (0, 1, 3)',
        'iss',
        array((int)$userID, $startDate, $stopDate)
    );

    if (!$result) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return 0;
    }

    $row = db_fetch_one($result);
    return (int)$row['CNT'] + get_business_trip_missing_data_count($link, $userID);
}

function get_accounting_errors_notification_count($link, $supervisorID)
{
    list($startDate, $stopDate) = accounting_errors_get_range();
    $supervisedUserIDs = get_accounting_errors_supervised_user_ids($link, $supervisorID);

    if (is_array($supervisedUserIDs)) {
        foreach ($supervisedUserIDs as $userID) {
            sync_business_trip_missing_data_for_user($link, (int)$userID);
        }
    }

    $result = db_query(
        $link,
        'SELECT COUNT(DISTINCT ae.ID) AS CNT
         FROM accounting_errors ae
         INNER JOIN GROUPS g ON g.USERID = ae.USERID
         WHERE g.SUPERVISORID = ? AND TRIM(g.TYPE) = ? AND ae.ERROR_DATE >= ? AND ae.ERROR_DATE <= ? AND ae.STATUS = 1 AND ae.USERID NOT IN (156, 161, 600)',
        'iiss',
        array((int)$supervisorID, 3, $startDate, $stopDate)
    );

    if (!$result) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return 0;
    }

    $row = db_fetch_one($result);
    $tripResult = db_query(
        $link,
        "SELECT COUNT(DISTINCT trip.ID) AS CNT
         FROM business_trip_missing_data trip
         INNER JOIN GROUPS g ON g.USERID = trip.USERID
         WHERE g.SUPERVISORID = ?
           AND TRIM(g.TYPE) = ?
           AND trip.TRIP_DATE >= ?
           AND trip.TRIP_DATE <= ?",
        'iiss',
        array((int)$supervisorID, 3, $startDate, $stopDate)
    );

    if (!$tripResult) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return (int)$row['CNT'];
    }

    $tripRow = db_fetch_one($tripResult);
    return (int)$row['CNT'] + (int)$tripRow['CNT'];
}

function get_accounting_errors_counts_by_user($link, $userID, &$totalCount, &$acceptedCount, &$refusedCount, &$deletedCount, &$newCount, &$businessTripCount = null)
{
    list($startDate, $stopDate) = accounting_errors_get_range();

    $totalCount = 0;
    $acceptedCount = 0;
    $refusedCount = 0;
    $deletedCount = 0;
    $newCount = 0;
    $businessTripCount = 0;

    if (is_accounting_errors_exempt_user($userID)) {
        return true;
    }

    $result = db_query(
        $link,
        'SELECT STATUS, COUNT(*) AS CNT FROM accounting_errors WHERE USERID = ? AND ERROR_DATE >= ? AND ERROR_DATE <= ? GROUP BY STATUS',
        'iss',
        array((int)$userID, $startDate, $stopDate)
    );

    if (!$result) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return false;
    }

    while ($row = db_fetch_one($result)) {
        $status = (int)$row['STATUS'];
        $count = (int)$row['CNT'];
        $totalCount += $count;

        if ($status === 1) {
            $newCount += $count;
        } elseif ($status === 2) {
            $acceptedCount += $count;
        } elseif ($status === 3) {
            $refusedCount += $count;
        } elseif ($status === 4) {
            $deletedCount += $count;
        }
    }

    $businessTripCount = get_business_trip_missing_data_count($link, $userID);
    $totalCount += $businessTripCount;

    return true;
}

function get_accounting_errors_supervised_users($link, $supervisorID)
{
    $result = db_query(
        $link,
        "SELECT DISTINCT
           membership.USERID,
           employee.SURNAME,
           employee.FIRSTNAME,
           employee.LASTNAME
         FROM GROUPS membership
         INNER JOIN employees employee ON employee.ID = membership.USERID
         WHERE membership.SUPERVISORID = ?
           AND TRIM(membership.TYPE) = ?
           AND membership.USERID NOT IN (156, 161, 600)
         ORDER BY employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME",
        'ii',
        array((int)$supervisorID, 3)
    );

    if (!$result) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return false;
    }

    $users = array();

    while ($row = db_fetch_one($result)) {
        $users[] = array(
            'user_id' => (int)$row['USERID'],
            'user_name' => trim(
                $row['SURNAME'] . ' ' . $row['FIRSTNAME'] . ' ' . $row['LASTNAME']
            ),
        );
    }

    return $users;
}

function get_accounting_errors_supervised_user_ids($link, $supervisorID)
{
    $users = get_accounting_errors_supervised_users($link, $supervisorID);

    if ($users === false) {
        return false;
    }

    return array_column($users, 'user_id');
}
