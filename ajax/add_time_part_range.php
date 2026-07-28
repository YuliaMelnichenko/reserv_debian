<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_once __DIR__ . '/../inc/offsite_work.php';
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
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

$sv_ID = 0;

if ($row = db_fetch_one($supervisor_query)) {
  $sv_ID = (int)$row["SUPERVISORID"];
}

if ($sv_ID <= 0) {
  echo "Не найден руководитель для согласования";
  exit;
}

try {
  $dailyIntervals = build_offsite_work_daily_intervals(
    $add_time_part_start_date,
    $add_time_part_stop_date,
    $add_time_part_start_time,
    $add_time_part_stop_time
  );
}
catch (InvalidArgumentException $error) {
  echo $error->getMessage();
  exit;
}
catch (RuntimeException $error) {
  error_log('[TORI] Offsite work range rejected: ' . $error->getMessage());
  echo "Не удалось сформировать точный диапазон дат. Записи не добавлены";
  exit;
}
$allowedDates = array();
$includeAllDays = ((int)$add_time_part_base == 5);

if ($exclude_weekend_holidays == 1 && !$includeAllDays) {
  $weekendsHolidays = get_workdays_holidays_bay_range($add_time_part_start_date, $add_time_part_stop_date);

  foreach ($dailyIntervals as $interval) {
    $rangeDay = $interval['date'];
    $found = -1;

    for ($idx = 0; $idx < count($weekendsHolidays[0]); $idx++) {
      if ($rangeDay == $weekendsHolidays[0][$idx]) {
        $found = $weekendsHolidays[1][$idx];
        break;
      }
    }

    if ($found == -1) {
      if (isWeekEnd($rangeDay) == 0) {
        $allowedDates[] = $rangeDay;
      }
    }
    else if ($found != 0) {
      $allowedDates[] = $rangeDay;
    }
  }

  $dailyIntervals = filter_offsite_work_intervals_by_dates($dailyIntervals, $allowedDates);
}

if (count($dailyIntervals) == 0) {
  echo "В выбранном диапазоне нет дней для добавления";
  exit;
}

$transaction = db_transaction_start($link);
if (!$transaction) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

$err = "";
$insertedCount = 0;

foreach ($dailyIntervals as $interval) {
  $query = db_execute_affected_rows($link, "INSERT INTO ADD_TIME
    (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT)
    VALUES
    (?, ?, ?, ?, ?, ?, ?, '', 0, 0, ?)", 'siissisi', array(
      $currentDate,
      $sv_ID,
      $userID_,
      $interval['start'],
      $interval['stop'],
      $add_time_part_base,
      $add_time_part_desk,
      $byAlert
    ));

  if ($query !== 1) {
    $err = ajax_database_error_message($link, __FILE__ . ':' . __LINE__);
    break;
  }

  $insertedCount++;
}

if ($err === "" && $insertedCount !== count($dailyIntervals)) {
  $err = "Не все даты выбранного диапазона были сохранены";
}

if (
  $err === ""
  && !clear_unsubmitted_accounting_errors_for_dates(
    $link,
    $userID_,
    array_column($dailyIntervals, 'date')
  )
) {
  $err = ajax_database_error_message($link, __FILE__ . ':' . __LINE__);
}

if ($err == "" && $insertedCount === count($dailyIntervals)) {
  if (!$transaction->commit()) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  $_SESSION['accounting_errors_sync_date'] = date('Y-m-d');
  echo "1";
}
else {
  $transaction->rollback();
  echo $err;
}
?>
