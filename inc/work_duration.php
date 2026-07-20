<?php

function is_time_defined($time)
{
    if (is_array($time)) {
        return count($time) > 0 ? 1 : 0;
    }

    if (!is_string($time) && !is_numeric($time)) {
        return 0;
    }

    $time = trim((string)$time);

    return !in_array($time, array('', 'NDF', '00:00:00', '0000-00-00 00:00:00'), true) ? 1 : 0;
}

function get_defined_time_range_duration($startTime, $stopTime)
{
    if (is_time_defined($startTime) === 0 || is_time_defined($stopTime) === 0) {
        return 0;
    }

    $startTimestamp = strtotime($startTime);
    $stopTimestamp = strtotime($stopTime);

    if ($startTimestamp === false || $stopTimestamp === false || $stopTimestamp <= $startTimestamp) {
        return 0;
    }

    return $stopTimestamp - $startTimestamp;
}

function add_time_entry_is_active($entry)
{
    if (!is_array($entry)) {
        return false;
    }

    $status = isset($entry[4]) ? (int)$entry[4] : 0;
    return !in_array($status, array(-1, 99, 100, 101), true);
}

function get_pause_time_duration_by_times($addTimeInfo)
{
    if (!is_array($addTimeInfo)) {
        return 0;
    }

    $duration = 0;

    foreach ($addTimeInfo as $entry) {
        if (
            is_array($entry)
            && isset($entry[7])
            && (int)$entry[7] === 1
            && add_time_entry_is_active($entry)
        ) {
            $duration += get_defined_time_range_duration($entry[0] ?? null, $entry[1] ?? null);
        }
    }

    return $duration;
}

function get_work_time_duration_by_times_ex(
    $inTime,
    $outTime,
    $eatStartTime,
    $eatStopTime,
    $state,
    $currentDay,
    $currentDateTime = null
) {
    if ((string)$state === 'NDF') {
        return 0;
    }

    $state = (int)$state;

    if ($state === 0) {
        return get_defined_time_range_duration($inTime, $outTime);
    }

    if ((int)$currentDay !== 1) {
        return 0;
    }

    if ($currentDateTime === null) {
        $timeResult = get_current_datetime_in_timezone();
        $currentDateTime = $timeResult[1];
    }

    return get_defined_time_range_duration($inTime, $currentDateTime);
}

function get_eat_time_duration_by_times_ex(
    $eatStartTime,
    $eatStopTime,
    $state,
    $currentDay,
    $currentDateTime = null
) {
    if ((string)$state === 'NDF') {
        return 0;
    }

    $state = (int)$state;

    if ($state === 0 || $state === 4) {
        return get_defined_time_range_duration($eatStartTime, $eatStopTime);
    }

    if ($state !== 3 || (int)$currentDay !== 1) {
        return 0;
    }

    if ($currentDateTime === null) {
        $timeResult = get_current_datetime_in_timezone();
        $currentDateTime = $timeResult[1];
    }

    return get_defined_time_range_duration($eatStartTime, $currentDateTime);
}

function get_add_time_duration_by_times_ex($addTimeInfo)
{
    if (!is_array($addTimeInfo)) {
        return 0;
    }

    $duration = 0;

    foreach ($addTimeInfo as $entry) {
        if (
            is_array($entry)
            && isset($entry[7])
            && (int)$entry[7] === 0
            && add_time_entry_is_active($entry)
        ) {
            $duration += get_defined_time_range_duration($entry[0] ?? null, $entry[1] ?? null);
        }
    }

    return $duration;
}

function get_durations(
    $inTime,
    $outTime,
    $eatStartTime,
    $eatStopTime,
    $addTimeInfo,
    $state,
    $currentDay,
    $currentDateTime = null,
    $defaultStartTime = null,
    $allowedDelay = null
) {
    if ($defaultStartTime === null) {
        $defaultStartTime = $_SESSION['ss_defaultStartTime'] ?? '00:00:00';
    }
    if ($allowedDelay === null) {
        $allowedDelay = (int)($_SESSION['ss_allowedDelay'] ?? 0);
    }

    $workDuration = get_work_time_duration_by_times_ex(
        $inTime,
        $outTime,
        $eatStartTime,
        $eatStopTime,
        $state,
        $currentDay,
        $currentDateTime
    );
    $eatDuration = get_eat_time_duration_by_times_ex(
        $eatStartTime,
        $eatStopTime,
        $state,
        $currentDay,
        $currentDateTime
    );
    $addTimeDuration = get_add_time_duration_by_times_ex($addTimeInfo);
    $pauseDuration = get_pause_time_duration_by_times($addTimeInfo);
    $delayDescription = 0;
    $allowedStartTimestamp = strtotime($defaultStartTime) + ((int)$allowedDelay * 60);
    $inTimestamp = strtotime($inTime);

    if ($inTimestamp !== false && $inTimestamp > $allowedStartTimestamp) {
        $delayDescription = $inTimestamp . ' ** ' . $allowedStartTimestamp . '  ---   '
            . ($inTimestamp - $allowedStartTimestamp);
    }

    return array(
        0 => $workDuration,
        1 => $eatDuration,
        2 => $addTimeDuration,
        3 => max(0, $workDuration + $addTimeDuration - $eatDuration - $pauseDuration),
        4 => $delayDescription,
        5 => $pauseDuration,
    );
}
