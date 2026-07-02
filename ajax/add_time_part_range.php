<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if (!isset($_SESSION['ss_id'])) {
  echo "Ошибка: сессия пользователя не найдена";
  exit;
}

$userID_ = $_SESSION['ss_id'];
$currentDate = date('Y-m-d');

$add_time_part_start_date = isset($_POST['add_time_part_start_date']) ? $_POST['add_time_part_start_date'] : "";
$add_time_part_stop_date = isset($_POST['add_time_part_stop_date']) ? $_POST['add_time_part_stop_date'] : "";
$add_time_part_start_time = isset($_POST['add_time_part_start_time']) ? $_POST['add_time_part_start_time'] : "";
$add_time_part_stop_time = isset($_POST['add_time_part_stop_time']) ? $_POST['add_time_part_stop_time'] : "";
$add_time_part_base = isset($_POST['add_time_part_base']) ? $_POST['add_time_part_base'] : "";
$add_time_part_desk = isset($_POST['add_time_part_desk']) ? $_POST['add_time_part_desk'] : "";
$exclude_weekend_holidays = isset($_POST['exclude_weekend_holidays']) ? (int)$_POST['exclude_weekend_holidays'] : 0;

if (isset($_POST['byAlert']) && $_POST['byAlert'] == 1) {
  $byAlert = 1;
}
else {
  $byAlert = 0;
}

if ($add_time_part_start_date == "" || $add_time_part_stop_date == "") {
  echo "Укажите дату начала и дату окончания диапазона";
  exit;
}

if ($add_time_part_start_time == "" || $add_time_part_stop_time == "") {
  echo "Укажите время начала и время окончания работ";
  exit;
}

if (strtotime($add_time_part_start_date) > strtotime($add_time_part_stop_date)) {
  echo "Дата начала диапазона превышает дату окончания";
  exit;
}

if ($add_time_part_start_time >= $add_time_part_stop_time) {
  echo "Время начала работ должно быть меньше времени окончания работ";
  exit;
}

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

function get_days_range_inclusive($startDate, $stopDate){
  $days = array();

  for ($day = $startDate; strtotime($day) <= strtotime($stopDate); $day = date('Y-m-d', strtotime($day . ' +1 day'))) {
    $days[] = $day;
  }

  return $days;
}

mysqli_set_charset($link, "utf8");

$add_time_part_start_date = mysqli_real_escape_string($link, $add_time_part_start_date);
$add_time_part_stop_date = mysqli_real_escape_string($link, $add_time_part_stop_date);
$add_time_part_start_time = mysqli_real_escape_string($link, $add_time_part_start_time);
$add_time_part_stop_time = mysqli_real_escape_string($link, $add_time_part_stop_time);
$add_time_part_base = mysqli_real_escape_string($link, $add_time_part_base);
$add_time_part_desk = mysqli_real_escape_string($link, $add_time_part_desk);
$userID_ = mysqli_real_escape_string($link, $userID_);

$supervisor_query = mysqli_query($link, "SELECT SUPERVISORID FROM GROUPS WHERE TYPE = 100 AND USERID = '$userID_' LIMIT 1");

if (!$supervisor_query) {
  echo "<br>mysql_error = " . mysqli_error($link) . "<br>";
  exit;
}

$sv_ID = 0;

if ($row = mysqli_fetch_array($supervisor_query, MYSQLI_ASSOC)) {
  $sv_ID = $row["SUPERVISORID"];
}

$query0 = mysqli_query($link, "SELECT max(ID) FROM ADD_TIME");
$newID = 1;

if (!$query0) {
  echo "<br>mysql_error = " . mysqli_error($link) . "<br>";
  exit;
}
else if ($row = mysqli_fetch_array($query0)) {
  $newID = (int)$row[0] + 1;
}

$daysRange = get_days_range_inclusive($add_time_part_start_date, $add_time_part_stop_date);
$newDaysRange = array();

if ($exclude_weekend_holidays == 1) {
  $weekendsHolidays = get_workdays_holidays_bay_range($add_time_part_start_date, $add_time_part_stop_date);

  foreach ($daysRange as $rangeDay) {
    $found = -1;

    for ($idx = 0; $idx < count($weekendsHolidays[0]); $idx++) {
      if ($rangeDay == $weekendsHolidays[0][$idx]) {
        $found = $weekendsHolidays[1][$idx];
        break;
      }
    }

    if ($found == -1) {
      if (isWeekEnd($rangeDay) == 0) {
        $newDaysRange[] = $rangeDay;
      }
    }
    else if ($found != 0) {
      $newDaysRange[] = $rangeDay;
    }
  }
}
else {
  $newDaysRange = $daysRange;
}

if (count($newDaysRange) == 0) {
  echo "В выбранном диапазоне нет дней для добавления";
  exit;
}

$err = "";

foreach ($newDaysRange as $rDay) {
  $start = $rDay . " " . $add_time_part_start_time . ":00";
  $stop = $rDay . " " . $add_time_part_stop_time . ":00";

  if (strtotime($start) >= strtotime($stop)) {
    $err .= "Некорректный интервал времени для даты $rDay<br>";
    break;
  }

  $rDayEsc = mysqli_real_escape_string($link, $rDay);
  $startEsc = mysqli_real_escape_string($link, $start);
  $stopEsc = mysqli_real_escape_string($link, $stop);

  $query = mysqli_query($link, "INSERT INTO ADD_TIME 
    (ID, ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT)
    VALUES
    ('$newID', '$currentDate', '$sv_ID', '$userID_', '$startEsc', '$stopEsc', '$add_time_part_base', '$add_time_part_desk', '', '0', '0', '$byAlert')");

  if (!$query) {
    $err .= "mysql_error = " . mysqli_error($link) . "<br>";
    break;
  }

  $newID = $newID + 1;
}

if ($err == "") {
  echo "1";
}
else {
  echo $err;
}
?>