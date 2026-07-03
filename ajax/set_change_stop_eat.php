<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id'];
$currentDate = date('Y-m-d H:i:s');
$dayNumber = (int) ($_POST['currentDayNumber'] ?? 0);
$new_stop_eat_time = (string) ($_POST['add_stop_eat_time'] ?? '');

include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8"); 

if ($dayNumber == "1") {
  $res = db_execute(
    $link,
    'UPDATE visiting SET eat_stop_dt = ?, state = 4, changes = 1 WHERE user_id = ? AND DATE(in_dt) = DATE(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 3 DAY))',
    'si',
    array($new_stop_eat_time, $userID)
  );
  $merr=mysqli_error($link);
}
else {
  $res = db_execute(
    $link,
    'UPDATE visiting SET eat_stop_dt = ?, state = 4, changes = 1 WHERE user_id = ? AND DATE(in_dt) = DATE(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY))',
    'si',
    array($new_stop_eat_time, $userID)
  );
  $merr=mysqli_error($link);
}

if ( !$res ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
else {
  echo "2";
}

?>
