<?php

function getQuarterDates ($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }
  $month = (int)date('n', strtotime($date));
  $year = (int)date('Y', strtotime($date));

  if ($month >= 1 && $month <= 3) {
    $start = "$year-01-01";
    $end = "$year-03-31";
  } elseif ($month >= 4 && $month <= 6) {
    $start = "$year-04-01";
    $end = "$year-06-30";
  } elseif ($month >= 7 && $month <= 9) {
    $start = "$year-07-01";
    $end = "$year-09-30";
  } else {
    $start = "$year-10-01";
    $end = "$year-12-31";
  }

  $today = date("Y-m-d");

  if (strtotime($today) < strtotime($end)) {
    $end = $today;
  }

  return [$start, $end];
}

function getWeekDates($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }

  $dateValue = strtotime($date);
  $weekDayNumber = (int)date('N', $dateValue);

  $start = date('Y-m-d', strtotime('-' . ($weekDayNumber - 1) . ' days', $dateValue));
  $end = date('Y-m-d', $dateValue);

  return [$start, $end];
}

function getMonthDates($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }

  $start = date('Y-m-01', strtotime($date));
  $end = date('Y-m-d', strtotime($date));

  return [$start, $end];
}

function getPreviousMonthDates($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }

  $prevMonthDate = strtotime('first day of previous month', strtotime($date));

  $start = date('Y-m-01', $prevMonthDate);
  $end = date('Y-m-t', $prevMonthDate);

  return [$start, $end];
}

function getPreviousQuarterDates($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }

  list($currentQuarterStart) = getQuarterDates($date);
  $previousQuarterDate = date('Y-m-d', strtotime($currentQuarterStart . ' -1 day'));

  return getQuarterDates($previousQuarterDate);
}

function refreshReportPeriodDates($currDate) {
  if (!isset($_SESSION['rep_start_stop_date_mode'])) {
    $_SESSION['rep_start_stop_date_mode'] = 4;
  }

  if (!isset($_SESSION['rep_start_stop_date_set'])) {
    $_SESSION['rep_start_stop_date_set'] = 1;
  }

  $mode = (int)$_SESSION['rep_start_stop_date_mode'];

  if ($mode == 1) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getWeekDates($currDate);
  }
  else if ($mode == 2) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getMonthDates($currDate);
  }
  else if ($mode == 3) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getPreviousMonthDates($currDate);
  }
  else if ($mode == 4) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getQuarterDates($currDate);
  }
  else if ($mode == 5) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getPreviousQuarterDates($currDate);
  }
}
