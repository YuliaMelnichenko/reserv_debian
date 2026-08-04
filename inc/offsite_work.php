<?php

require_once __DIR__ . '/date_range.php';

function build_offsite_work_daily_intervals($startDate, $stopDate, $startTime, $stopTime)
{
    $startDate = normalize_date_value($startDate);
    $stopDate = normalize_date_value($stopDate);
    $startTime = normalize_time_value($startTime);
    $stopTime = normalize_time_value($stopTime);

    if ($startDate === null || $stopDate === null || $startDate > $stopDate) {
        throw new InvalidArgumentException('Некорректный диапазон дат');
    }

    if ($startTime === null || $stopTime === null || $startTime >= $stopTime) {
        throw new InvalidArgumentException('Некорректный интервал времени');
    }

    $days = get_days_range_inclusive($startDate, $stopDate);
    $expectedCount = (new DateTimeImmutable($startDate))
        ->diff(new DateTimeImmutable($stopDate))
        ->days + 1;

    if (count($days) !== $expectedCount) {
        throw new RuntimeException('Не удалось построить точный диапазон дат');
    }

    $intervals = array();

    foreach ($days as $day) {
        if ($day < $startDate || $day > $stopDate) {
            throw new RuntimeException('Дата вышла за границы выбранного диапазона');
        }

        $range = get_valid_datetime_range(
            $day . ' ' . $startTime,
            $day . ' ' . $stopTime
        );

        if (
            $range === null
            || substr($range['start'], 0, 10) !== $day
            || substr($range['stop'], 0, 10) !== $day
        ) {
            throw new RuntimeException('Интервал работы вышел за границы выбранного дня');
        }

        $intervals[] = array(
            'date' => $day,
            'start' => $range['start'],
            'stop' => $range['stop'],
        );
    }

    return $intervals;
}

function filter_offsite_work_intervals_by_dates($intervals, $allowedDates)
{
    $allowed = array_fill_keys($allowedDates, true);
    $filtered = array();

    foreach ($intervals as $interval) {
        if (isset($allowed[$interval['date']])) {
            $filtered[] = $interval;
        }
    }

    return $filtered;
}
