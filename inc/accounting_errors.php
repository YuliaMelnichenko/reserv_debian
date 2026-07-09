<?php

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
    return 180;
}

function accounting_errors_log_database_failure($link, $context)
{
    database_error_message($link, $context);
    return false;
}

function accounting_errors_get_range($depthDays)
{
    $depthDays = (int)$depthDays;

    if ($depthDays <= 0) {
        $depthDays = get_accounting_errors_default_depth_days();
    }

    $startDate = date('Y-m-d', strtotime('-' . $depthDays . ' days'));
    $stopDate = date('Y-m-d', strtotime('-1 day'));
    $stopExclusive = date('Y-m-d', strtotime($stopDate . ' +1 day'));

    return array($startDate, $stopDate, $stopExclusive);
}

function accounting_errors_remove_dates_from_result($result, $column, &$dates)
{
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row[$column])) {
            unset($dates[$row[$column]]);
        }
    }
}

function sync_accounting_errors_for_user($link, $userID, $depthDays = 0)
{
    $userID = (int)$userID;

    if ($userID <= 0) {
        return false;
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

    while ($row = mysqli_fetch_assoc($calendarResult)) {
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

    while ($leave = mysqli_fetch_assoc($leavesResult)) {
        $leaveStart = max($startDate, $leave['start_date']);
        $leaveStop = min($stopDate, $leave['stop_date']);
        $leaveDay = new DateTimeImmutable($leaveStart);
        $leaveLastDay = new DateTimeImmutable($leaveStop);

        while ($leaveDay <= $leaveLastDay) {
            unset($missingDates[$leaveDay->format('Y-m-d')]);
            $leaveDay = $leaveDay->modify('+1 day');
        }
    }

    if (!mysqli_begin_transaction($link)) {
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    $existingResult = db_query(
        $link,
        'SELECT ID, ERROR_DATE, STATUS FROM accounting_errors WHERE USERID = ? AND ERROR_DATE >= ? AND ERROR_DATE <= ? FOR UPDATE',
        'iss',
        array($userID, $startDate, $stopDate)
    );

    if (!$existingResult) {
        mysqli_rollback($link);
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    $existingDates = array();

    while ($existing = mysqli_fetch_assoc($existingResult)) {
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
                mysqli_rollback($link);
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

        $inserted = db_execute(
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

        if (!$inserted) {
            mysqli_rollback($link);
            return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        }

        $insertedCount += max(0, mysqli_affected_rows($link));
    }

    if (!mysqli_commit($link)) {
        mysqli_rollback($link);
        return accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
    }

    return $insertedCount;
}

function get_accounting_errors_count($link, $userID)
{
    $depthDays = get_accounting_errors_default_depth_days();
    list($startDate) = accounting_errors_get_range($depthDays);

    $result = db_query(
        $link,
        'SELECT COUNT(*) AS CNT FROM accounting_errors WHERE USERID = ? AND ERROR_DATE >= ? AND STATUS IN (0, 1, 3)',
        'is',
        array((int)$userID, $startDate)
    );

    if (!$result) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)$row['CNT'];
}

function get_accounting_errors_notification_count($link, $supervisorID)
{
    list($startDate) = accounting_errors_get_range(get_accounting_errors_default_depth_days());
    $result = db_query(
        $link,
        'SELECT COUNT(DISTINCT ae.ID) AS CNT
         FROM accounting_errors ae
         INNER JOIN GROUPS g ON g.USERID = ae.USERID
         WHERE g.SUPERVISORID = ? AND TRIM(g.TYPE) = ? AND ae.ERROR_DATE >= ? AND ae.STATUS = 1',
        'iis',
        array((int)$supervisorID, 3, $startDate)
    );

    if (!$result) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)$row['CNT'];
}

function get_accounting_errors_counts_by_user($link, $userID, &$totalCount, &$acceptedCount, &$refusedCount, &$deletedCount, &$newCount)
{
    $depthDays = get_accounting_errors_default_depth_days();
    list($startDate) = accounting_errors_get_range($depthDays);

    $totalCount = 0;
    $acceptedCount = 0;
    $refusedCount = 0;
    $deletedCount = 0;
    $newCount = 0;

    $result = db_query(
        $link,
        'SELECT STATUS, COUNT(*) AS CNT FROM accounting_errors WHERE USERID = ? AND ERROR_DATE >= ? GROUP BY STATUS',
        'is',
        array((int)$userID, $startDate)
    );

    if (!$result) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return false;
    }

    while ($row = mysqli_fetch_assoc($result)) {
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

    return true;
}

function get_accounting_errors_supervised_user_ids($link, $supervisorID)
{
    $result = db_query(
        $link,
        'SELECT DISTINCT USERID FROM GROUPS WHERE SUPERVISORID = ? AND TRIM(TYPE) = ? ORDER BY USERID',
        'ii',
        array((int)$supervisorID, 3)
    );

    if (!$result) {
        accounting_errors_log_database_failure($link, __FILE__ . ':' . __LINE__);
        return false;
    }

    $userIDs = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $userIDs[] = (int)$row['USERID'];
    }

    return $userIDs;
}
