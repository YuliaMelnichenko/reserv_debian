<?php

function GetWeekDay($date)
{
    return date('w', strtotime($date));
}

function GetMonthDay($date)
{
    return date('d', strtotime($date));
}

function GetWeekDayName($weekDay)
{
    $names = array(
        0 => 'Воскресенье',
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
    );

    return $names[(int)$weekDay] ?? null;
}

function GetMonthName($month)
{
    $names = array(
        1 => 'Январь',
        2 => 'Февраль',
        3 => 'Март',
        4 => 'Апрель',
        5 => 'Май',
        6 => 'Июнь',
        7 => 'Июль',
        8 => 'Август',
        9 => 'Сентябрь',
        10 => 'Октябрь',
        11 => 'Ноябрь',
        12 => 'Декабрь',
    );

    return $names[(int)$month] ?? null;
}

function DayInc($day)
{
    return strtotime('+1 day', $day);
}

function get_current_quarter_date_range($stopAtYesterday = false, $referenceDate = null)
{
    $referenceTimestamp = $referenceDate === null ? time() : strtotime($referenceDate);

    if ($referenceTimestamp === false) {
        $referenceTimestamp = time();
    }

    $month = (int)date('n', $referenceTimestamp);
    $year = (int)date('Y', $referenceTimestamp);
    $quarterStartMonth = intdiv($month - 1, 3) * 3 + 1;
    $startDate = date('Y-m-d', mktime(0, 0, 0, $quarterStartMonth, 1, $year));
    $stopTimestamp = $stopAtYesterday
        ? strtotime('-1 day', $referenceTimestamp)
        : $referenceTimestamp;
    $stopDate = date('Y-m-d', $stopTimestamp);
    $stopExclusive = date('Y-m-d', strtotime('+1 day', $stopTimestamp));

    return array($startDate, $stopDate, $stopExclusive);
}

function format_date_range_label($startDate, $stopDate)
{
    return date('d.m.Y', strtotime($startDate)) . ' - ' . date('d.m.Y', strtotime($stopDate));
}

function GetWeekDayD($date)
{
    return (int)date('N', strtotime($date));
}

function isWeekEnd($date)
{
    return GetWeekDayD($date) >= 6 ? 1 : 0;
}

function is_first_week_day($date)
{
    return GetWeekDayD($date) === 1 ? 1 : 0;
}

function is_first_month_day($date)
{
    return date('d', strtotime($date)) === '01' ? 1 : 0;
}

function is_first_quarter_day($date)
{
    $day = (int)GetMonthDayD($date);
    $month = (int)GetMonthD($date);

    return $day === 1 && in_array($month, array(1, 4, 7, 10), true) ? 1 : 0;
}

function is_first_year_day($date)
{
    return date('m-d', strtotime($date)) === '01-01' ? 1 : 0;
}

function GetMonthDayD($date)
{
    return date('d', strtotime($date));
}

function GetMonthD($date)
{
    return date('m', strtotime($date));
}

function GetCurrentYearD($date)
{
    return date('Y', strtotime($date));
}

function GetCurrentDate()
{
    return date('Y-m-d');
}

function GetFirstYearDay($year)
{
    return date('Y-m-d', mktime(0, 0, 0, 1, 1, $year));
}

function GetFirstYearDayEx($date)
{
    return GetFirstYearDay(GetCurrentYearD($date));
}

function GetFirstMonthDayEx($date)
{
    return DayIncDN($date, 1 - (int)GetMonthDayD($date));
}

function GetFirstQuarterDayEx($date)
{
    return MonthDecDN($date, 3);
}

function GetWeekDayNameD($date)
{
    $weekDay = GetWeekDayD($date);
    $names = array(
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    );

    return $names[$weekDay] ?? null;
}

function GetMonthNameByDate($date)
{
    return GetMonthName((int)GetMonthD($date));
}

function GetQuarterRomNumByDate($date)
{
    $quarter = intdiv((int)GetMonthD($date) - 1, 3) + 1;
    $names = array(1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV');

    return $names[$quarter] ?? null;
}

function DayIncDN($day, $count)
{
    set_time_limit(120);
    return date('Y-m-d', strtotime("+$count day", strtotime($day)));
}

function DayDecDN($day, $count)
{
    return date('Y-m-d', strtotime("-$count day", strtotime($day)));
}

function set_to_first_month_day($date)
{
    return DayDecDN($date, (int)GetMonthDayD($date) - 1);
}

function MonthDecDN($day, $count)
{
    if ((int)$count === 0) {
        return $day;
    }

    return date('Y-m-d', strtotime("-$count month", strtotime($day)));
}

function GetFirstMonthDay($date)
{
    return date('Y-m-d', mktime(0, 0, 0, GetMonthD($date), 1, GetCurrentYearD($date)));
}

function GetLastMonthDay($date)
{
    $firstDay = GetFirstMonthDay($date);
    return date('Y-m-d', strtotime('+1 month -1 day', strtotime($firstDay)));
}
