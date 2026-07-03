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
 
$superUserID = (int) ($_POST['superuserID'] ?? -1);
$description = (string) ($_POST['desk'] ?? '');

$dtResult = get_current_datetime_in_timezone();

$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];

$query = db_execute($link, 'UPDATE visiting SET take_pause = 1 WHERE id = ? AND user_id = ?', 'ii', array($ss_visiting_ID, $userID));
$merr=mysqli_error($link);

if (!$query){
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else{
  mysqli_set_charset($link, "utf8");
  
$query = db_execute($link, "INSERT INTO ADD_TIME (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT) VALUES (?, ?, ?, ?, '0000-00-00 00:00:00', -1, ?, '', 0, 1, 0)", 'siiss', array($currentDate, $superUserID, $userID, $currentDateTime, $description));

  $merr=mysqli_error($link);
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
