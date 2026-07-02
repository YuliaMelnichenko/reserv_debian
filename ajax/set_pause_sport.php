<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

mysqli_set_charset($link, "utf8");

$userID = $_SESSION['ss_id'];
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];

$description = $_POST['desk'];

$dtResult = get_current_datetime_in_timezone();

$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];

$supervisor_query = mysqli_query($link,"SELECT SUPERVISORID FROM GROUPS WHERE TYPE = 100 AND USERID = '$userID'");
$row = mysqli_fetch_array($supervisor_query);

$sv_ID = $row["SUPERVISORID"];

$query = mysqli_query($link, "UPDATE visiting SET take_pause = '1' WHERE id = '$ss_visiting_ID' AND user_id = '$userID'");
$merr = mysqli_error($link);

if (!$query) {
  echo "<br>mysql_error = $merr<br>";
}
else {
  mysqli_set_charset($link, "utf8"); 
  $query = mysqli_query($link, "INSERT INTO ADD_TIME (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT ) VALUES ('$currentDate', '$sv_ID', '$userID', '$currentDateTime', '0000-00-00 00:00:00', '-1', '$description', '', '0', '1', '0')");

  $merr = mysqli_error($link);
  
  if (!$query) {
    echo "<br>mysql_error = $merr<br>";
  }
  else {    
    echo "1"; 
  }
}
?>