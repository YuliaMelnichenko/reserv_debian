<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id'];

$date_train = (string) ($_POST['date_train'] ?? '');
$start_time = (string) ($_POST['start_time'] ?? '');
$stop_time = (string) ($_POST['stop_time'] ?? '');

include __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8"); 

$res = db_execute(
  $link,
  'DELETE FROM gym_schedule WHERE USERID = ? AND DATE_TRAIN = ? AND START_TIME = ? AND STOP_TIME = ?',
  'isss',
  array($userID, $date_train, $start_time, $stop_time)
);
$merr = mysqli_error($link);

if ( !$res ) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
else {
  echo "2";
}
?>
