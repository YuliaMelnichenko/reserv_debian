<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id'];
$currentDate = date('Y-m-d H:i:s');
$dayNumber = $_POST['currentDayNumber'];
$new_out_time = $_POST['add_stop_time'];

include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

if ($dayNumber == "1") {
  $res = mysqli_query($link, "UPDATE visiting SET out_dt = '$new_out_time', state = 0, changes = 1 WHERE user_id = '$userID' and DATE(in_dt) = DATE(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 3 DAY))");
  $log = mysqli_query($link, "INSERT INTO logging_changes (USER_ID, DATE_CHANGE, CHANGES) VALUES ('$userID', NOW(), '$new_out_time') ");
  $merr = mysqli_error($link);
}
else {
  $res = mysqli_query($link, "UPDATE visiting SET out_dt = '$new_out_time', state = 0, changes = 1 WHERE user_id = '$userID' and DATE(in_dt) = DATE(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY))");
  $log = mysqli_query($link, "INSERT INTO logging_changes (USER_ID, DATE_CHANGE, CHANGES) VALUES ('$userID', NOW(), '$new_out_time')");
  $merr = mysqli_error($link);
}

if ( !$res ) 
{
  echo "<br>mysql_error = $merr<br>";
} 
else {
  echo "2";
}

?>