<?php

function normalize_datetime_value($value)
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    $formats = array('Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i');

    foreach ($formats as $format) {
        $dateTime = DateTimeImmutable::createFromFormat('!' . $format, $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (
            $dateTime !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
        ) {
            return $dateTime->format('Y-m-d H:i:s');
        }
    }

    return null;
}

function normalize_date_value($value)
{
    if (!is_string($value)) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
    $errors = DateTimeImmutable::getLastErrors();

    if (
        $date === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
    ) {
        return null;
    }

    return $date->format('Y-m-d');
}

function normalize_time_value($value)
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);

    foreach (array('H:i:s', 'H:i') as $format) {
        $time = DateTimeImmutable::createFromFormat('!' . $format, $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (
            $time !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
        ) {
            return $time->format('H:i:s');
        }
    }

    return null;
}

function get_valid_datetime_range($startDateTime, $stopDateTime)
{
    $start = normalize_datetime_value($startDateTime);
    $stop = normalize_datetime_value($stopDateTime);

    if ($start === null || $stop === null) {
        return null;
    }

    $startTimestamp = strtotime($start);
    $stopTimestamp = strtotime($stop);

    if ($startTimestamp === false || $stopTimestamp === false || $stopTimestamp <= $startTimestamp) {
        return null;
    }

    return array(
        'start' => $start,
        'stop' => $stop,
        'start_timestamp' => $startTimestamp,
        'stop_timestamp' => $stopTimestamp,
        'duration' => $stopTimestamp - $startTimestamp,
    );
}

function clip_datetime_range($startDateTime, $stopDateTime, $rangeStart, $rangeStop)
{
    $source = get_valid_datetime_range($startDateTime, $stopDateTime);
    $bounds = get_valid_datetime_range($rangeStart, $rangeStop);

    if ($source === null || $bounds === null) {
        return null;
    }

    $startTimestamp = max($source['start_timestamp'], $bounds['start_timestamp']);
    $stopTimestamp = min($source['stop_timestamp'], $bounds['stop_timestamp']);

    if ($stopTimestamp <= $startTimestamp) {
        return null;
    }

    return array(
        'start' => date('Y-m-d H:i:s', $startTimestamp),
        'stop' => date('Y-m-d H:i:s', $stopTimestamp),
        'duration' => $stopTimestamp - $startTimestamp,
    );
}

function split_datetime_range_by_day($startDateTime, $stopDateTime)
{
    $range = get_valid_datetime_range($startDateTime, $stopDateTime);

    if ($range === null) {
        return array();
    }

    $segments = array();
    $segmentStart = $range['start_timestamp'];

    while ($segmentStart < $range['stop_timestamp']) {
        $nextDay = strtotime(date('Y-m-d 00:00:00', $segmentStart) . ' +1 day');
        $segmentStop = min($nextDay, $range['stop_timestamp']);

        $segments[] = array(
            'date' => date('Y-m-d', $segmentStart),
            'start' => date('Y-m-d H:i:s', $segmentStart),
            'stop' => date('Y-m-d H:i:s', $segmentStop),
            'duration' => $segmentStop - $segmentStart,
        );

        $segmentStart = $segmentStop;
    }

    return $segments;
}

function get_days_range_inclusive($startDate, $stopDate)
{
    $normalizedStart = normalize_date_value($startDate);
    $normalizedStop = normalize_date_value($stopDate);

    if ($normalizedStart === null || $normalizedStop === null) {
        return array();
    }

    $start = new DateTimeImmutable($normalizedStart);
    $stop = new DateTimeImmutable($normalizedStop);

    if ($stop < $start) {
        return array();
    }

    $days = array();

    for ($day = $start; $day <= $stop; $day = $day->modify('+1 day')) {
        $days[] = $day->format('Y-m-d');
    }

    return $days;
}
