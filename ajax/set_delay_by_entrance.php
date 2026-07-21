<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if (request_post_has('userID'))
{
  $userId = request_post_int('userID');
}
else
{
  $userId = (int) $_SESSION['ss_id'];
}

require_ajax_self_or_supervisor($userId, 3);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$ss_delay_duration = (int)$_SESSION['ss_delay_duration'];
$ss_delay_duration_db = format_time_d_hhmmss_pure($ss_delay_duration);

$currentDateArr = get_current_datetime_in_timezone();
$currentDate = $currentDateArr[2];

if (!mysqli_begin_transaction($link)) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$idQuery = db_query($link, 'SELECT ID FROM Delays ORDER BY ID DESC LIMIT 1 FOR UPDATE');

if (!$idQuery) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$lastDelay = mysqli_fetch_assoc($idQuery);
$newID = $lastDelay ? (int)$lastDelay['ID'] + 1 : 1;

$query = db_query(
  $link,
  'SELECT ID FROM Delays WHERE userID = ? AND date = ? FOR UPDATE',
  'is',
  array($userId, $currentDate)
);

if (!$query) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$delayExists = mysqli_num_rows($query) > 0;

if (!$delayExists)
{
  $query = db_execute(
    $link,
    "INSERT INTO Delays (ID, date, duration, userID, supervisorID, explaneDesk, acceptorID, penaltyID, penaltyReply, status)
     VALUES (?, ?, ?, ?, -1, 'Без объяснения', -1, -1, '', 0)",
    'issi',
    array($newID, $currentDate, $ss_delay_duration_db, $userId)
  );

  if (!$query)
  {
    mysqli_rollback($link);
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
  }          
}

if (!mysqli_commit($link)) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

if (!$delayExists) {
  echo "insert";
  $_SESSION['ss_ch_delay_ID'] = $newID;
} else {
  echo "exist";
}
?>
