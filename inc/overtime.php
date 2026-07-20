<?php

require_once __DIR__ . '/date_range.php';

function getPeriodBounds(string $period, $currentDate = null): array
{
    $date = normalize_date_value($currentDate === null ? date('Y-m-d') : $currentDate);

    if ($date === null) {
        throw new InvalidArgumentException('Некорректная текущая дата');
    }

    $today = new DateTimeImmutable($date);

    switch ($period) {
        case 'week':
            $start = $today->modify('monday this week');
            break;
        case 'month':
            $start = $today->modify('first day of this month');
            break;
        case 'quarter':
        default:
            $quarterStartMonth = ((int)floor(((int)$today->format('n') - 1) / 3) * 3) + 1;
            $start = $today->setDate((int)$today->format('Y'), $quarterStartMonth, 1);
            break;
    }

    return array($start->format('Y-m-d 00:00:00'), $today->format('Y-m-d 23:59:59'));
}

function getOvertimePeriodBounds($period, $customStart = '', $customStop = '', $currentDate = null)
{
    if ($period !== 'custom') {
        return getPeriodBounds((string)$period, $currentDate);
    }

    $start = normalize_date_value((string)$customStart);
    $stop = normalize_date_value((string)$customStop);

    if ($start === null || $stop === null) {
        throw new InvalidArgumentException('Не заданы корректные даты для ручного ввода');
    }

    if ($stop < $start) {
        throw new InvalidArgumentException('Дата окончания периода меньше даты начала');
    }

    return array($start . ' 00:00:00', $stop . ' 23:59:59');
}

function normalizeOvertimeThreshold($hours, $fallback = 9.0)
{
    if (!is_numeric($hours)) {
        return (float)$fallback;
    }

    $hours = (float)$hours;
    return $hours > 0 ? $hours : (float)$fallback;
}

function calculateOvertimeDayHours($officeHours, $outsideHours, $pauseHours)
{
    return max(0.0, (float)$officeHours + (float)$outsideHours - (float)$pauseHours);
}

function formatHours($hours)
{
    $minutes = (int)round((float)$hours * 60);

    if ($minutes <= 0) {
        return '—';
    }

    $hoursPart = intdiv($minutes, 60);
    $minutesPart = $minutes % 60;

    if ($hoursPart > 0 && $minutesPart > 0) {
        return "$hoursPart ч $minutesPart мин";
    }

    if ($hoursPart > 0) {
        return "$hoursPart ч";
    }

    return "$minutesPart мин";
}

function overtimeNumbersSql(): string
{
    $digits = "SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9";

    return "
        SELECT ones.n + tens.n * 10 + hundreds.n * 100 AS n
        FROM ($digits) ones
        CROSS JOIN ($digits) tens
        CROSS JOIN ($digits) hundreds
        WHERE ones.n + tens.n * 10 + hundreds.n * 100 <= 730
    ";
}

function overtimeAddTimeSqlParts(): array
{
    $numbersSql = overtimeNumbersSql();
    $workDateSql = "DATE_ADD(DATE(a.START_DT), INTERVAL n.n DAY)";
    $rangeSql = "
        a.START_DT < ? AND a.STOP_DT > ?
        AND $workDateSql >= DATE(?)
        AND $workDateSql <= DATE(?)
        AND a.START_DT IS NOT NULL
        AND a.START_DT != '0000-00-00 00:00:00'
        AND a.STOP_DT IS NOT NULL
        AND a.STOP_DT != '0000-00-00 00:00:00'
        AND a.STOP_DT > a.START_DT
    ";
    $durationSql = "
        GREATEST(
            0,
            TIME_TO_SEC(
                TIMEDIFF(
                    LEAST(a.STOP_DT, DATE_ADD($workDateSql, INTERVAL 1 DAY)),
                    GREATEST(a.START_DT, $workDateSql)
                )
            )
        )
    ";

    return array($numbersSql, $workDateSql, $rangeSql, $durationSql);
}
