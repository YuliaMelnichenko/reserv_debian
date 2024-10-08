<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$userID = $_SESSION['ss_id']; 
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];

$pauseID = $_POST['pauseID'];

include_once "/var/www/tori/nv/funcs.php";
include_once "/var/www/tori/nv/php_tori/connect.php";

$dtResult = get_current_datetime_in_timezone();

$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];

$query = mysqli_query($link, "update visiting set take_pause = '0' where id = '$ss_visiting_ID' and user_id = '$userID'");
$merr=mysqli_error($link);
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  $query = mysqli_query($link, "update ADD_TIME set STOP_DT = '$currentDateTime' where id = '$pauseID'");

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


                                                                         