<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID_ = (int)$_SESSION['ss_id'];

$training_date = (string) ($_POST['training_date'] ?? '');
$training_start_time = (string) ($_POST['training_start_time'] ?? '');
$training_stop_time = (string) ($_POST['training_stop_time'] ?? '');

include __DIR__ . "/../php_tori/connect.php";

$dateParts = explode('-', $training_date);
$validDate = count($dateParts) === 3
  && ctype_digit($dateParts[0])
  && ctype_digit($dateParts[1])
  && ctype_digit($dateParts[2])
  && checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]);
$validTimePattern = '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/';

if (
  !$validDate
  || !preg_match($validTimePattern, $training_start_time)
  || !preg_match($validTimePattern, $training_stop_time)
  || $training_date < date('Y-m-d')
  || strtotime('1970-01-01 ' . $training_start_time) >= strtotime('1970-01-01 ' . $training_stop_time)
) {
  deny_ajax_access(400, 'INVALID_SCHEDULE');
}

if (!mysqli_begin_transaction($link)) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$idQuery = db_query($link, 'SELECT ID FROM gym_schedule ORDER BY ID DESC LIMIT 1 FOR UPDATE');

if (!$idQuery) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$lastSchedule = mysqli_fetch_assoc($idQuery);
$newID = $lastSchedule ? (int)$lastSchedule['ID'] + 1 : 1;

$duplicateQuery = db_query(
  $link,
  'SELECT ID FROM gym_schedule WHERE USERID = ? AND DATE_TRAIN = ? AND START_TIME = ? AND STOP_TIME = ? LIMIT 1 FOR UPDATE',
  'isss',
  array($userID_, $training_date, $training_start_time, $training_stop_time)
);

if (!$duplicateQuery) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

if (mysqli_num_rows($duplicateQuery) > 0) {
  if (!mysqli_commit($link)) {
    mysqli_rollback($link);
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  echo "2";
  exit;
}

$slotQuery = db_query(
  $link,
  'SELECT USERID FROM gym_schedule WHERE DATE_TRAIN = ? AND START_TIME = ? AND STOP_TIME = ? FOR UPDATE',
  'sss',
  array($training_date, $training_start_time, $training_stop_time)
);

if (!$slotQuery) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$slotUsers = array();

while ($slotRow = mysqli_fetch_assoc($slotQuery)) {
  $slotUsers[(int)$slotRow['USERID']] = true;
}

if (count($slotUsers) >= 4) {
  mysqli_rollback($link);
  echo "1";
  exit;
}

$query = db_execute(
  $link,
  'INSERT INTO gym_schedule (ID, USERID, DATE_TRAIN, START_TIME, STOP_TIME) VALUES (?, ?, ?, ?, ?)',
  'iisss',
  array($newID, $userID_, $training_date, $training_start_time, $training_stop_time)
);

if (!$query) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

if (!mysqli_commit($link)) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

echo "2";
?>
