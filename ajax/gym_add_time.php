<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID_ = $_SESSION['ss_id']; 

$training_date = (string) ($_POST['training_date'] ?? '');
$training_start_time = (string) ($_POST['training_start_time'] ?? '');
$training_stop_time = (string) ($_POST['training_stop_time'] ?? '');

include __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

$query0 = mysqli_query($link, "SELECT max(ID) FROM gym_schedule"); 
$newID = 0;

if ( !$query0 ) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
else if ($row = mysqli_fetch_array($query0)) {
  $newID = $row[0] + 1;
}

$query = db_execute($link, 'INSERT INTO gym_schedule (ID, USERID, DATE_TRAIN, START_TIME, STOP_TIME) VALUES (?, ?, ?, ?, ?)', 'iisss', array($newID, $userID_, $training_date, $training_start_time, $training_stop_time));

$merr=mysqli_error($link);

if (!$query) {
  $err .= "mysql_error = $merr<br>";
}
else {
  $newID = $newID + 1;
}

if ( $err == "" ) {
  echo "2";       
} 
else {
  echo $err;       
} 
?>
