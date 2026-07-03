<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id']; 
$currentDate = date('Y-m-d');
$currentTime = date("H:i:s");
$pauseID = (int) ($_POST['pauseID'] ?? 0);
require_ajax_add_time_access($pauseID);

include_once __DIR__ . "/../php_tori/connect.php";

$query = db_execute(
  $link,
  'UPDATE visiting SET take_pause = 0 WHERE date = ? AND user_id = ?',
  'si',
  array($currentDate, $userID)
);
$merr = mysqli_error($link);
if (!$query)
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else
{
$query = db_execute($link, 'UPDATE ADD_TIME SET STOPTIME = ? WHERE id = ?', 'si', array($currentTime, $pauseID));

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
