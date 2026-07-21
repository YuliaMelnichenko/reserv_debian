<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if (!isset($_SESSION['ss_id'])) {
  echo "Ошибка: сессия пользователя не найдена";
  exit;
}

$userID_ = (int)$_SESSION['ss_id'];
$currentDate = date('Y-m-d');

$add_time_part_start_date = request_post_date('add_time_part_start_date');
$add_time_part_stop_date = request_post_date('add_time_part_stop_date');
$add_time_part_start_time = request_post_time('add_time_part_start_time');
$add_time_part_stop_time = request_post_time('add_time_part_stop_time');
$add_time_part_base = request_post_int('add_time_part_base');
$add_time_part_desk = request_post_trimmed_string('add_time_part_desk');
$exclude_weekend_holidays = request_post_int('exclude_weekend_holidays');

$byAlert = request_post_int('byAlert') === 1 ? 1 : 0;

if ($add_time_part_start_date === null || $add_time_part_stop_date === null) {
  echo "Укажите дату начала и дату окончания диапазона";
  exit;
}

if ($add_time_part_start_time === null || $add_time_part_stop_time === null) {
  echo "Некорректное время начала или окончания";
  exit;
}

if ($add_time_part_start_date > $add_time_part_stop_date) {
  echo "Дата начала диапазона превышает дату окончания";
  exit;
}

if ($add_time_part_start_time >= $add_time_part_stop_time) {
  echo "Время начала работ должно быть меньше времени окончания работ";
  exit;
}

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

if ($add_time_part_base <= 0) {
  echo "Не выбрано основание работы вне офиса";
  exit;
}

$supervisor_query = db_query($link, "SELECT SUPERVISORID FROM GROUPS WHERE TYPE = 100 AND USERID = ? LIMIT 1", 'i', array($userID_));

if (!$supervisor_query) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$sv_ID = 0;

if ($row = mysqli_fetch_array($supervisor_query, MYSQLI_ASSOC)) {
  $sv_ID = (int)$row["SUPERVISORID"];
}

if ($sv_ID <= 0) {
  echo "Не найден руководитель для согласования";
  exit;
}

$daysRange = get_days_range_inclusive($add_time_part_start_date, $add_time_part_stop_date);
$newDaysRange = array();
$includeAllDays = ((int)$add_time_part_base == 5);

if ($exclude_weekend_holidays == 1 && !$includeAllDays) {
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

if (!mysqli_begin_transaction($link)) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$err = "";

foreach ($newDaysRange as $rDay) {
  $start = $rDay . " " . $add_time_part_start_time;
  $stop = $rDay . " " . $add_time_part_stop_time;
  $range = get_valid_datetime_range($start, $stop);

  if ($range === null) {
    $err .= "Некорректный интервал времени для даты $rDay<br>";
    break;
  }

  $start = $range['start'];
  $stop = $range['stop'];

  $query = db_execute($link, "INSERT INTO ADD_TIME
    (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT)
    VALUES
    (?, ?, ?, ?, ?, ?, ?, '', 0, 0, ?)", 'siissisi', array($currentDate, $sv_ID, $userID_, $start, $stop, $add_time_part_base, $add_time_part_desk, $byAlert));

  if (!$query) {
    $err = database_error_message($link, __FILE__ . ':' . __LINE__);
    break;
  }
}

if ($err == "") {
  if (!mysqli_commit($link)) {
    mysqli_rollback($link);
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  echo "1";
}
else {
  mysqli_rollback($link);
  echo $err;
}
?>
