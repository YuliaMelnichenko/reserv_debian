<?php

require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/date_range.php';

function get_journal_period($mode, $manualStartDate = null, $manualStopDate = null, $referenceDate = null)
{
    $mode = (int)$mode;

    if (!in_array($mode, array(1, 2, 3, 4, 5, 6, 7), true)) {
        return null;
    }

    $referenceTimestamp = $referenceDate === null ? time() : strtotime($referenceDate);

    if ($referenceTimestamp === false) {
        return null;
    }

    $currentDate = date('Y-m-d', $referenceTimestamp);

    if ($mode === 1) {
        $weekDay = (int)date('N', $referenceTimestamp);
        $startDate = date('Y-m-d', strtotime('-' . ($weekDay - 1) . ' days', $referenceTimestamp));
        $stopDate = $currentDate;
    } elseif ($mode === 2) {
        $startDate = date('Y-m-01', $referenceTimestamp);
        $stopDate = $currentDate;
    } elseif ($mode === 3) {
        $previousMonthTimestamp = strtotime('first day of previous month', $referenceTimestamp);
        $startDate = date('Y-m-01', $previousMonthTimestamp);
        $stopDate = date('Y-m-t', $previousMonthTimestamp);
    } elseif ($mode === 4) {
        list($startDate, $stopDate) = get_current_quarter_date_range(false, $currentDate);
    } elseif ($mode === 5) {
        list($currentQuarterStartDate) = get_current_quarter_date_range(false, $currentDate);
        $previousQuarterStopTimestamp = strtotime($currentQuarterStartDate . ' -1 day');
        $stopDate = date('Y-m-d', $previousQuarterStopTimestamp);
        $startDate = date('Y-m-01', strtotime($stopDate . ' -2 months'));
    } elseif ($mode === 6) {
        list($startDate, $stopDate) = get_add_time_period_date_range($currentDate);
    } else {
        $manualStartDate = normalize_date_value($manualStartDate);
        $manualStopDate = normalize_date_value($manualStopDate);
        if ($manualStartDate === null || $manualStopDate === null || $manualStartDate > $manualStopDate) {
            return null;
        }

        $rangeDays = (strtotime($manualStopDate) - strtotime($manualStartDate)) / 86400;

        if ($rangeDays > 366) {
            return null;
        }

        $startDate = $manualStartDate;
        $stopDate = $manualStopDate;
    }

    return array(
        'mode' => $mode,
        'start_date' => $startDate,
        'stop_date' => $stopDate,
        'stop_exclusive' => date('Y-m-d', strtotime($stopDate . ' +1 day')),
    );
}

function get_journal_period_from_session($sessionPrefix, $referenceDate = null, $defaultMode = 4)
{
    $sessionPrefix = (string)$sessionPrefix;
    $mode = isset($_SESSION[$sessionPrefix . '_mode'])
        ? (int)$_SESSION[$sessionPrefix . '_mode']
        : (int)$defaultMode;
    $manualStartDate = isset($_SESSION[$sessionPrefix . '_start_date'])
        ? $_SESSION[$sessionPrefix . '_start_date']
        : null;
    $manualStopDate = isset($_SESSION[$sessionPrefix . '_stop_date'])
        ? $_SESSION[$sessionPrefix . '_stop_date']
        : null;
    $period = get_journal_period($mode, $manualStartDate, $manualStopDate, $referenceDate);

    if ($period !== null) {
        return $period;
    }

    return get_journal_period($defaultMode, null, null, $referenceDate);
}
