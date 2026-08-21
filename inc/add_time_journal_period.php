<?php

require_once __DIR__ . '/calendar.php';

function get_add_time_journal_period($mode, $manualStartDate = null, $manualStopDate = null, $referenceDate = null)
{
    $mode = (int)$mode;

    if (!in_array($mode, array(1, 2, 3, 4, 5, 7), true)) {
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
    } else {
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

function get_add_time_journal_period_from_session($referenceDate = null)
{
    $mode = isset($_SESSION['add_time_journal_period_mode'])
        ? (int)$_SESSION['add_time_journal_period_mode']
        : 4;
    $manualStartDate = isset($_SESSION['add_time_journal_period_start_date'])
        ? $_SESSION['add_time_journal_period_start_date']
        : null;
    $manualStopDate = isset($_SESSION['add_time_journal_period_stop_date'])
        ? $_SESSION['add_time_journal_period_stop_date']
        : null;
    $period = get_add_time_journal_period($mode, $manualStartDate, $manualStopDate, $referenceDate);

    if ($period !== null) {
        return $period;
    }

    return get_add_time_journal_period(4, null, null, $referenceDate);
}
