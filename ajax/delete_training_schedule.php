<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id'];

$date_train = $_POST['date_train'];
$start_time = $_POST['start_time'];
$stop_time = $_POST['stop_time'];

include __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8"); 

$res = mysqli_query($link, "DELETE FROM gym_schedule WHERE USERID='$userID' AND DATE_TRAIN='$date_train' AND START_TIME='$start_time' AND STOP_TIME='$stop_time'");
$merr = mysqli_error($link);

if ( !$res ) {
  echo "<br>mysql_error = $merr<br>";
} 
else {
  echo "2";
}
?>