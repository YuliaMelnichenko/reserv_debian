<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  exit;
}

if (!request_post_has('report_type')) {
  exit;
}

$report_type = request_post_int('report_type');

if ($report_type < 1 || $report_type > 7) {
  exit;
}

ajax_text_headers();

include_once __DIR__ . "/../funcs.php";

$currDate = date('Y-m-d');

$start_report_date = request_post_date('start_report_date');
$stop_report_date = request_post_date('stop_report_date');

error_log("CALL set_report_date_interval: " . ajax_encode_json(array(
  'report_type' => $report_type,
  'start_report_date' => $start_report_date,
  'stop_report_date' => $stop_report_date,
)));

if ($report_type == 7) {
  if ($start_report_date === null || $stop_report_date === null || $stop_report_date < $start_report_date) {
    die('Ошибка: неверный диапазон дат');
  }

  $customRangeDays = (strtotime($stop_report_date) - strtotime($start_report_date)) / 86400;

  if ($customRangeDays > 366) {
    die('Слишком большой диапазон дат');
  }
}

unset($_SESSION['rep_start_date']);
unset($_SESSION['rep_stop_date']);

$_SESSION['rep_start_stop_date_mode'] = $report_type;
$_SESSION['rep_start_stop_date_set'] = 0;

if ($report_type == 1) {
  $week_day = GetWeekDayD($currDate);
  $offset = $week_day - 1;

  $_SESSION['rep_start_date'] = DayDecDN($currDate, $offset);
  $_SESSION['rep_stop_date'] = $currDate;
  $_SESSION['rep_start_stop_date_set'] = 2;
}
else if ($report_type == 2) {
  $month_day = GetMonthDayD($currDate);
  $offset = $month_day - 1;

  $_SESSION['rep_start_date'] = DayDecDN($currDate, $offset);
  $_SESSION['rep_stop_date'] = $currDate;
  $_SESSION['rep_start_stop_date_set'] = 2;
}
else if ($report_type == 3) {
  $prevMonthDate = strtotime('first day of previous month', strtotime($currDate));

  $_SESSION['rep_start_date'] = date('Y-m-01', $prevMonthDate);
  $_SESSION['rep_stop_date'] = date('Y-m-t', $prevMonthDate);
  $_SESSION['rep_start_stop_date_set'] = 2;
}
else if ($report_type == 4) {
  $month = (int)date('n', strtotime($currDate));
  $year = (int)date('Y', strtotime($currDate));

  if ($month >= 1 && $month <= 3) {
    $_SESSION['rep_start_date'] = "$year-01-01";
  }
  else if ($month >= 4 && $month <= 6) {
    $_SESSION['rep_start_date'] = "$year-04-01";
  }
  else if ($month >= 7 && $month <= 9) {
    $_SESSION['rep_start_date'] = "$year-07-01";
  }
  else {
    $_SESSION['rep_start_date'] = "$year-10-01";
  }

  $_SESSION['rep_stop_date'] = $currDate;
  $_SESSION['rep_start_stop_date_set'] = 2;
}
else if ($report_type == 5) {
  $month = (int)date('n', strtotime($currDate));
  $year = (int)date('Y', strtotime($currDate));
  $currentQuarter = (int)ceil($month / 3);
  $prevQuarter = $currentQuarter - 1;

  if ($prevQuarter <= 0) {
    $prevQuarter = 4;
    $year--;
  }

  $startMonth = ($prevQuarter - 1) * 3 + 1;
  $startDate = sprintf('%04d-%02d-01', $year, $startMonth);
  $endDate = date('Y-m-t', strtotime($startDate . ' +2 months'));

  $_SESSION['rep_start_date'] = $startDate;
  $_SESSION['rep_stop_date'] = $endDate;
  $_SESSION['rep_start_stop_date_set'] = 2;
}
else if ($report_type == 6) {
  $_SESSION['rep_start_date'] = GetFirstYearDay(GetCurrentYearD($currDate));
  $_SESSION['rep_stop_date'] = $currDate;
  $_SESSION['rep_start_stop_date_set'] = 2;
}
else if ($report_type == 7) {
  $_SESSION['rep_start_date'] = $start_report_date;
  $_SESSION['rep_stop_date'] = $stop_report_date;
  $_SESSION['rep_start_stop_date_set'] = 2;
}

if (empty($_SESSION['rep_start_date']) || empty($_SESSION['rep_stop_date'])) {
  error_log("ERROR: даты не установлены!");
  die('Ошибка: период не определен');
}

$diff = (strtotime($_SESSION['rep_stop_date']) - strtotime($_SESSION['rep_start_date'])) / 86400;

if ($diff < 0) {
  die('Ошибка: неверный диапазон дат');
}

if ($diff > 366) {
  die('Слишком большой диапазон дат');
}

unset($_SESSION['full_report']);
unset($_SESSION['usersInfo']);
unset($_SESSION['report_stats']);
unset($_SESSION['rowsContents']);

$_SESSION['report_cache_date'] = $currDate;
$_SESSION['report_cache_mode'] = $_SESSION['rep_start_stop_date_mode'];
$_SESSION['report_cache_start_date'] = $_SESSION['rep_start_date'];
$_SESSION['report_cache_stop_date'] = $_SESSION['rep_stop_date'];

echo $report_type . "_";
echo "start = " . $_SESSION['rep_start_date'] . " stop = " . $_SESSION['rep_stop_date'];
?>
