<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/errors.php';

function sort_employee($first, $second)
{
    $presenceComparison = (int)$first[12] <=> (int)$second[12];

    if ($presenceComparison !== 0) {
        return $presenceComparison;
    }

    return mb_strtolower($first[0], 'UTF-8') <=> mb_strtolower($second[0], 'UTF-8');
}

function index_presence_status($employeeId, $visit, $openAddTime, $remoteWork)
{
    $emptyDateTime = '0000-00-00 00:00:00';
    $hasVisit = is_array($visit);
    $inDateTime = $hasVisit ? (string)$visit['in_dt'] : '';
    $lunchStart = $hasVisit ? (string)$visit['eat_start_dt'] : '';
    $lunchStop = $hasVisit ? (string)$visit['eat_stop_dt'] : '';
    $outDateTime = $hasVisit ? (string)$visit['out_dt'] : '';
    $isLunchPause = $hasVisit
        && $lunchStart !== ''
        && $lunchStart !== $emptyDateTime
        && ($lunchStop === '' || $lunchStop === $emptyDateTime);
    $hasGoneHome = $hasVisit
        && $inDateTime !== ''
        && $inDateTime !== $emptyDateTime
        && $outDateTime !== ''
        && $outDateTime !== $emptyDateTime;
    $isRemoteNow = is_array($remoteWork) && is_null($remoteWork['stop_dt']);

    if ($isLunchPause || is_array($openAddTime)) {
        return array(
            "<img class=\"work-status\" data-emp=\"$employeeId\" title=\"Обед/приостановка времени\" src=\"img/pause_time.png\">",
            1,
        );
    }

    if ($isRemoteNow) {
        return array(
            "<img class=\"work-status presence-inline-icon\" data-emp=\"$employeeId\" title=\"Работает удаленно\" src=\"img/remoteWorkIcon2.png\">",
            1,
        );
    }

    if (!$hasVisit) {
        return array(
            "<img class=\"work-status presence-inline-icon\" data-emp=\"$employeeId\" title=\"На работу не приходил\" src=\"img/home.png\">",
            0,
        );
    }

    if ($hasGoneHome) {
        return array(
            "<img class=\"work-status\" data-emp=\"$employeeId\" title=\"Ушел домой\" src=\"img/go_home.png\">",
            2,
        );
    }

    return array(
        "<img class=\"work-status presence-inline-icon\" data-emp=\"$employeeId\" title=\"На рабочем месте\" src=\"img/in_work2.png\">",
        1,
    );
}

function index_fetch_presence_rows($link)
{
    db_set_charset($link, 'utf8');
    $bossRows = db_fetch_all(db_query(
        $link,
        "SELECT id, firstname, surname, lastname, DATE_FORMAT(birthday, '%m-%d') AS birthday_month_day
         FROM employees
         WHERE id IN (400, 500, 501)"
    ));
    $bosses = array();

    foreach ($bossRows as $boss) {
        if (($boss['birthday_month_day'] ?? '') !== date('m-d')) {
            continue;
        }

        $bosses[] = array(
            trim($boss['surname'] . ' ' . $boss['firstname'] . ' ' . $boss['lastname']),
            '',
            '',
            '<img class="presence-inline-icon" title="C днем рождения!" src="img/birthday.png">',
            '',
            '',
            '',
            '',
            '',
            $boss['id'],
            $boss['birthday_month_day'],
            '',
            0,
        );
    }

    $employeeResult = db_query($link, "
        SELECT id,
               firstname,
               surname,
               lastname,
               phone,
               personal_phone,
               corporate_phone,
               DATE_FORMAT(birthday, '%m-%d') AS birthday_month_day,
               email
        FROM employees
        WHERE relevance = 1
          AND id NOT IN (400, 500, 501)
        ORDER BY surname
    ");

    if (!$employeeResult) {
        throw new RuntimeException(database_error_message($link, __FILE__ . ':' . __LINE__));
    }

    $employees = array();
    $emptyDateTime = '0000-00-00 00:00:00';

    foreach (db_fetch_all($employeeResult) as $employee) {
        $employeeId = (int)$employee['id'];
        $visitResult = db_query(
            $link,
            'SELECT in_dt, eat_start_dt, eat_stop_dt, out_dt
             FROM visiting
             WHERE DATE(in_dt) = CURDATE() AND user_id = ?
             ORDER BY in_dt DESC, ID DESC
             LIMIT 1',
            'i',
            array($employeeId)
        );
        $addTimeResult = db_query(
            $link,
            "SELECT START_DT, STOP_DT
             FROM ADD_TIME
             WHERE DATE(START_DT) = CURDATE()
               AND USERID = ?
               AND (STOP_DT IS NULL OR STOP_DT = '0000-00-00 00:00:00')
             ORDER BY START_DT DESC, ID DESC
             LIMIT 1",
            'i',
            array($employeeId)
        );
        $remoteResult = db_query(
            $link,
            'SELECT id, start_dt, stop_dt
             FROM remote_work
             WHERE user_id = ? AND DATE(start_dt) = CURDATE()
             ORDER BY id DESC
             LIMIT 1',
            'i',
            array($employeeId)
        );

        if (!$visitResult || !$addTimeResult || !$remoteResult) {
            throw new RuntimeException(database_error_message($link, __FILE__ . ':' . __LINE__));
        }

        $visit = db_fetch_one($visitResult);
        $addTime = db_fetch_one($addTimeResult);
        $remoteWork = db_fetch_one($remoteResult);
        list($statusIcon, $sortOrder) = index_presence_status(
            $employeeId,
            $visit,
            $addTime,
            $remoteWork
        );
        $inDateTime = $visit ? (string)$visit['in_dt'] : '';
        $outDateTime = $visit ? (string)$visit['out_dt'] : '';

        $employees[] = array(
            $employee['surname'] . ' ' . $employee['firstname'] . ' ' . $employee['lastname'],
            ($inDateTime !== '' && $inDateTime !== $emptyDateTime) ? date('H:i', strtotime($inDateTime)) : '',
            ($outDateTime !== '' && $outDateTime !== $emptyDateTime) ? date('H:i', strtotime($outDateTime)) : '',
            $statusIcon,
            $inDateTime,
            $outDateTime,
            $employee['phone'],
            $employee['personal_phone'],
            $employee['corporate_phone'],
            $employeeId,
            $employee['birthday_month_day'],
            $employee['email'],
            $sortOrder,
        );
    }

    usort($employees, 'sort_employee');

    return array_merge($bosses, $employees);
}

function get_phone_info($employeeId, $phone, $personalPhone, $corporatePhone, $email)
{
    $tooltipId = 'u' . (int)$employeeId . '-contacts';
    $contacts = array('Телефон внутренний: ' . htmlspecialchars($phone));

    if (!empty($personalPhone)) {
        $contacts[] = 'Мобильный: ' . htmlspecialchars($personalPhone);
    }

    if (!empty($corporatePhone)) {
        $contacts[] = 'Служебный мобильный: ' . htmlspecialchars($corporatePhone);
    }

    if (!empty($email)) {
        $contacts[] = 'Эл. почта: ' . htmlspecialchars($email);
    }

    echo '<div class="phone_tooltip" data-phone-tooltip-target="' . $tooltipId . '">';
    echo implode('<br>', $contacts);
    echo '</div>';
}

function getHolidayDates($link, $formDate = null)
{
    $formDate = $formDate ?: date('Y-m-d');
    $result = db_query(
        $link,
        'SELECT date FROM work_dayoff WHERE type = 0 AND date >= ?',
        's',
        array($formDate)
    );
    $holidays = array();

    foreach (db_fetch_all($result) as $row) {
        $holidays[] = $row['date'];
    }

    return $holidays;
}

function getWorkingDaysUntil($today, $startDate, $holidays = array())
{
    $start = new DateTime($today);
    $end = new DateTime($startDate);

    if ($start >= $end) {
        return 0;
    }

    $workingDays = 0;
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);

    foreach ($period as $date) {
        $isWeekend = (int)$date->format('N') >= 6;
        $isHoliday = in_array($date->format('Y-m-d'), $holidays, true);

        if (!$isWeekend && !$isHoliday) {
            $workingDays++;
        }
    }

    return $workingDays;
}

function getDayWord($number)
{
    $number = (int)$number;
    $lastDigit = $number % 10;
    $lastTwoDigits = $number % 100;

    if ($lastTwoDigits >= 11 && $lastTwoDigits <= 14) {
        return 'дней';
    }

    if ($lastDigit === 1) {
        return 'день';
    }

    if ($lastDigit >= 2 && $lastDigit <= 4) {
        return 'дня';
    }

    return 'дней';
}

function getDaysLeft($endDate, $today)
{
    return (new DateTime($today))->diff(new DateTime($endDate))->days + 1;
}

function index_fetch_staff_events($link, $today)
{
    $result = db_query($link, "
        SELECT user_id, event, start_date, stop_date
        FROM staff_leaves
        WHERE (
          (event = 'Больничный' AND ? BETWEEN start_date AND stop_date)
          OR
          (event = 'Отпуск' AND (? BETWEEN start_date AND stop_date OR start_date >= ?))
          OR
          (event = 'Командировка' AND (? BETWEEN start_date AND stop_date OR start_date >= ?))
        )
    ", 'sssss', array($today, $today, $today, $today, $today));
    $events = array();

    foreach (db_fetch_all($result) as $row) {
        $userId = (int)$row['user_id'];
        $candidate = array(
            'event' => $row['event'],
            'start_date' => $row['start_date'],
            'stop_date' => $row['stop_date'],
        );
        $duplicate = false;

        foreach ($events[$userId] ?? array() as $event) {
            if ($event === $candidate) {
                $duplicate = true;
                break;
            }
        }

        if (!$duplicate) {
            $events[$userId][] = $candidate;
        }
    }

    return $events;
}
