<?php

function time_to_second($timeStr)
{
    $timestamp = strtotime($timeStr);

    if ($timestamp === false) {
        return 0;
    }

    return ((int)date('H', $timestamp) * 3600)
        + ((int)date('i', $timestamp) * 60)
        + (int)date('s', $timestamp);
}

function format_time_($seconds)
{
    $seconds = max(0, (int)$seconds);
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remainingSeconds = $seconds % 60;
    $timePart = sprintf('%02d:%02d:%02d', $hours % 24, $minutes, $remainingSeconds);

    if ($hours < 24) {
        return $timePart;
    }

    return intdiv($hours, 24) . 'д ' . $timePart;
}

function format_time_hour_min($seconds)
{
    $seconds = round_to_minute((int)$seconds);
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);

    return sprintf('%02d:%02d', $hours, $minutes);
}

function format_time_differs_from_norm_hour_min($seconds, $norm)
{
    $seconds = round_to_minute((int)$seconds);
    $actualMinutes = intdiv($seconds, 60);
    $normMinutes = (int)$norm * 60;
    $differenceMinutes = abs($actualMinutes - $normMinutes);

    return sprintf('%02d:%02d', intdiv($differenceMinutes, 60), $differenceMinutes % 60);
}

function time_defined($time)
{
    return $time === '00:00:00' ? 0 : 1;
}

function round_to_minute($seconds)
{
    $seconds = (int)$seconds;
    $wholeMinutes = (int)($seconds / 60);
    $remainingSeconds = $seconds - ($wholeMinutes * 60);
    $rounded = $wholeMinutes * 60;

    if ($remainingSeconds > 30) {
        $rounded += 60;
    }

    return $rounded;
}

function work_day_duration($inTime, $outTime, $eatStart, $eatStop, $addTimeDuration)
{
    if (
        time_defined($inTime) === 0
        || time_defined($outTime) === 0
        || time_defined($eatStart) === 0
        || time_defined($eatStop) === 0
    ) {
        return (int)$addTimeDuration === 0 ? '-1' : format_time_d($addTimeDuration);
    }

    return format_time_d(
        strtotime($outTime)
        - strtotime($inTime)
        - (strtotime($eatStop) - strtotime($eatStart))
        + (int)$addTimeDuration
    );
}

function format_time_d($seconds)
{
    return '<font size="2" color="#000000" face="Arial">'
        . format_time_d_hhmmss_pure($seconds)
        . '</font>';
}

function format_time_d_hhmm_pure($seconds)
{
    $seconds = (int)$seconds;
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds - ($hours * 3600), 60);
    $remainingSeconds = $seconds - ($hours * 3600) - ($minutes * 60);

    if ($remainingSeconds >= 30) {
        $minutes++;
    }

    if ($minutes >= 60) {
        $hours += intdiv($minutes, 60);
        $minutes %= 60;
    }

    return sprintf('%02d:%02d', $hours, $minutes);
}

function format_time_d_hhmmss_pure($seconds)
{
    $seconds = (int)$seconds;

    if ($seconds < 0) {
        return 'ERR (time<0)';
    }

    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remainingSeconds = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
}

function format_time_d_hhmmss_pure_partial($seconds)
{
    $seconds = (int)$seconds;

    if ($seconds < 0) {
        return 'ERR (time<0)';
    }

    return sprintf('%2.2f', $seconds / 3600);
}

function format_time_d_hhmmss_pure_HH($seconds)
{
    $seconds = (int)$seconds;

    if ($seconds < 0) {
        return 'ERR (time<0)';
    }

    return sprintf('%02d', intdiv($seconds, 3600));
}

function format_time_d_hhmmss_pure_styled($seconds)
{
    $result = format_time_d_hhmmss_pure($seconds);
    $class = (int)$seconds > 0 ? 'middle' : 'middleGrey';

    return '<h5 class="' . $class . '">' . $result . '</h5>';
}
