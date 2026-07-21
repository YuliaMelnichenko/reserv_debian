<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

mysqli_set_charset($link, "utf8");

$userID = $_SESSION['ss_id'];
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];

$description = request_post_string('desk');

$dtResult = get_current_datetime_in_timezone();

$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];

$supervisor_query = db_query($link, 'SELECT SUPERVISORID FROM GROUPS WHERE TYPE = 100 AND USERID = ?', 'i', array($userID));
$row = mysqli_fetch_array($supervisor_query);

$sv_ID = $row["SUPERVISORID"];

$query = db_execute($link, 'UPDATE visiting SET take_pause = 1 WHERE id = ? AND user_id = ?', 'ii', array($ss_visiting_ID, $userID));
$merr = mysqli_error($link);

if (!$query) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
}
else {
  mysqli_set_charset($link, "utf8"); 
$query = db_execute($link, "INSERT INTO ADD_TIME (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT) VALUES (?, ?, ?, ?, '0000-00-00 00:00:00', -1, ?, '', 0, 1, 0)", 'siiss', array($currentDate, $sv_ID, $userID, $currentDateTime, $description));

  $merr = mysqli_error($link);
  
  if (!$query) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
  }
  else {    
    echo "1"; 
  }
}
?>
