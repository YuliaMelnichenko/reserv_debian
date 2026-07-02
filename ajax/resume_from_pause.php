<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id']; 
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];

$pauseID = $_POST['pauseID'];

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$dtResult = get_current_datetime_in_timezone();

$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];

$query = mysqli_query($link, "UPDATE visiting SET take_pause = '0' WHERE id = '$ss_visiting_ID' AND user_id = '$userID'");
$merr=mysqli_error($link);
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  $query = mysqli_query($link, "UPDATE ADD_TIME SET STOP_DT = '$currentDateTime' WHERE id = '$pauseID'");

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