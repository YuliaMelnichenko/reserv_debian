<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/employee_directory.php';

function GetHourNormByMonth($date, $rate)
{
    include __DIR__ . '/../php_tori/connect.php';

    $query = db_query(
        $link,
        'SELECT dur40, dur36, dur24 FROM factory_calendar WHERE date = ?',
        's',
        array($date)
    );

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return 0;
    }

    if (mysqli_num_rows($query) > 1) {
        return 'Error 378. Dublicate factory calendar dates';
    }

    $row = mysqli_fetch_assoc($query);

    if (!$row) {
        return 0;
    }

    if ((int)$rate === 40) {
        return $row['dur40'];
    }

    if ((int)$rate === 36) {
        return $row['dur36'];
    }

    if ((int)$rate === 24) {
        return $row['dur24'];
    }

    return 0;
}

function get_workdays_holidays_bay_range($startDate, $stopDate)
{
    include __DIR__ . '/../php_tori/connect.php';

    $query = db_query(
        $link,
        'SELECT DISTINCT DATE, TYPE FROM work_dayoff WHERE DATE >= ? AND DATE <= ?',
        'ss',
        array($startDate, $stopDate)
    );

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return array(array(), array());
    }

    $dates = array();
    $types = array();

    while ($row = mysqli_fetch_assoc($query)) {
        $dates[] = $row['DATE'];
        $types[] = $row['TYPE'];
    }

    return array($dates, $types);
}

function get_holidays()
{
    include __DIR__ . '/../php_tori/connect.php';

    $query = db_query($link, 'SELECT DATE FROM work_dayoff WHERE TYPE = 0');

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return array();
    }

    $holidays = array();
    $index = 1;

    while ($row = mysqli_fetch_assoc($query)) {
        $holidays[$index] = $row['DATE'];
        $index++;
    }

    return $holidays;
}

function get_work_day()
{
    include __DIR__ . '/../php_tori/connect.php';

    $query = db_query($link, 'SELECT DATE FROM work_dayoff WHERE TYPE = 1');

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return array();
    }

    $workDays = array();
    $index = 1;

    while ($row = mysqli_fetch_assoc($query)) {
        $workDays[$index] = $row['DATE'];
        $index++;
    }

    return $workDays;
}

function get_days_range($startDate, $stopDate)
{
    $daysRange = array();
    $index = 1;

    for ($date = $startDate; ; $date = DayIncDN($date, 1)) {
        $daysRange[$index] = $date;
        $index++;

        if ($date == $stopDate) {
            break;
        }
    }

    return $daysRange;
}

function get_days_wo_weekends($daysRange)
{
    $days = array();
    $index = 1;

    for ($sourceIndex = 1; $sourceIndex <= count($daysRange); $sourceIndex++) {
        if (!isWeekEnd($daysRange[$sourceIndex])) {
            $days[$index] = $daysRange[$sourceIndex];
            $index++;
        }
    }

    return $days;
}

function get_days_wo_holidays($daysRange)
{
    $holidays = get_holidays();
    $days = array();
    $index = 1;

    for ($sourceIndex = 1; $sourceIndex <= count($daysRange); $sourceIndex++) {
        $found = false;

        for ($holidayIndex = 1; $holidayIndex <= count($holidays); $holidayIndex++) {
            if ($daysRange[$sourceIndex] == $holidays[$holidayIndex]) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $days[$index] = $daysRange[$sourceIndex];
            $index++;
        }
    }

    return $days;
}

function get_days_with_add_workdays($daysRange)
{
    $workDays = get_work_day();
    $days = $daysRange;
    $index = count($daysRange) + 1;

    for ($workDayIndex = 1; $workDayIndex <= count($workDays); $workDayIndex++) {
        $found = false;

        for ($dayIndex = 1; $dayIndex <= count($daysRange); $dayIndex++) {
            if ($workDays[$workDayIndex] == $daysRange[$dayIndex]) {
                $found = true;
                break;
            }
        }

        if ($found) {
            $days[$index] = $workDays[$workDayIndex];
            $index++;
        }
    }

    return $days;
}

function max_date($daysRange)
{
    if (count($daysRange) === 0) {
        return '';
    }

    $maxDate = $daysRange[1];

    for ($index = 1; $index <= count($daysRange); $index++) {
        if (strtotime($daysRange[$index]) > strtotime($maxDate)) {
            $maxDate = $daysRange[$index];
        }
    }

    return $maxDate;
}

function min_date($daysRange)
{
    if (count($daysRange) === 0) {
        return '';
    }

    $minDate = $daysRange[1];

    for ($index = 1; $index < count($daysRange); $index++) {
        if (strtotime($daysRange[$index]) < strtotime(max_date($daysRange))) {
            $minDate = $daysRange[$index];
        }
    }

    return $minDate;
}

function get_norm_by_range_sec($startDate, $stopDate, $userID)
{
    $rate = get_user_rate($userID);
    $daysRange = get_days_range($startDate, $stopDate);
    $daysRange = get_days_wo_weekends($daysRange);
    $daysRange = get_days_wo_holidays($daysRange);
    $daysRange = get_days_with_add_workdays($daysRange);
    $normaByDay = $rate / 5;

    return $normaByDay * count($daysRange) * 60 * 60;
}

function get_norm_time_by_current_day_sec($user_defaultStartHour, $user_defaultStartMinute)
{
    $hours = date('H');
    $minutes = date('i');
    $seconds = date('s');
    $currentTime = $hours * 60 + $minutes;
    $defaultStartTime = $user_defaultStartHour * 60 + $user_defaultStartMinute;

    return ($currentTime - $defaultStartTime) * 60 + $seconds;
}

function apply_staff_leaves_to_days_norm($link, $userID, $startDate, $stopDate, $days_dates_set, $days_norm)
{
    $query = db_query($link, "
        SELECT start_date, stop_date, event
        FROM staff_leaves
        WHERE user_id = ?
          AND event IN ('Отпуск', 'Больничный')
          AND start_date <= ?
          AND stop_date >= ?
    ", 'iss', array((int)$userID, $stopDate, $startDate));

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return $days_norm;
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $leaveStart = max($row['start_date'], $startDate);
        $leaveStop = min($row['stop_date'], $stopDate);

        for ($index = 0; $index < count($days_dates_set); $index++) {
            $day = $days_dates_set[$index];

            if ($day >= $leaveStart && $day <= $leaveStop) {
                $days_norm[$index] = 0;
            }
        }
    }

    return $days_norm;
}

function get_staff_leave_events_by_days($link, $userID, $startDate, $stopDate, $days_dates_set)
{
    $leaveEvents = array_fill(0, count($days_dates_set), 'NDF');
    $query = db_query($link, "
        SELECT start_date, stop_date, event
        FROM staff_leaves
        WHERE user_id = ?
          AND event IN ('Отпуск', 'Больничный')
          AND start_date <= ?
          AND stop_date >= ?
    ", 'iss', array((int)$userID, $stopDate, $startDate));

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return $leaveEvents;
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $leaveStart = max($row['start_date'], $startDate);
        $leaveStop = min($row['stop_date'], $stopDate);

        for ($index = 0; $index < count($days_dates_set); $index++) {
            $day = $days_dates_set[$index];

            if ($day >= $leaveStart && $day <= $leaveStop) {
                $leaveEvents[$index] = $row['event'];
            }
        }
    }

    return $leaveEvents;
}

function get_work_dayoff_types_by_range($link, $startDate, $stopDate)
{
    $query = db_query($link, "
        SELECT date, type
        FROM work_dayoff
        WHERE date >= ?
          AND date <= ?
          AND type IN (0, 1, 2)
    ", 'ss', array($startDate, $stopDate));

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return array();
    }

    $result = array();

    while ($row = mysqli_fetch_assoc($query)) {
        $result[$row['date']] = (int)$row['type'];
    }

    return $result;
}
