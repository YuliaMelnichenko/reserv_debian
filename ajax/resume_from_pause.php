<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = $_SESSION['ss_id']; 
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];

$pauseID = request_post_int('pauseID');
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
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else
{
$query = db_execute($link, 'UPDATE ADD_TIME SET STOP_DT = ? WHERE id = ?', 'si', array($currentDateTime, $pauseID));

  if (!$query)
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    echo "1"; 
  }
}
?>
