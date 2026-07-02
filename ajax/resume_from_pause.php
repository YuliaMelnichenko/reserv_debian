<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id']; 
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];

$pauseID = (int) ($_POST['pauseID'] ?? 0);
require_ajax_add_time_access($pauseID);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$dtResult = get_current_datetime_in_timezone();

$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];

$query = db_execute(
  $link,
  'UPDATE visiting SET take_pause = 0 WHERE id = ? AND user_id = ?',
  'ii',
  array($ss_visiting_ID, $userID)
);
$merr=mysqli_error($link);
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
$query = db_execute($link, 'UPDATE ADD_TIME SET STOP_DT = ? WHERE id = ?', 'si', array($currentDateTime, $pauseID));

  if (!$query)
  {
    echo "<br>mysql_error = $merr<br>";
  }
  else
  {
    echo "1"; 
  }
}
?>
