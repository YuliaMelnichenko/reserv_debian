<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id']; 
$currentDate = date('Y-m-d');
$currentTime = date("H:i:s");
$pauseID = $_POST['pauseID'];

include_once __DIR__ . "/../php_tori/connect.php";

$query = mysqli_query($link, "UPDATE visiting SET take_pause = '0' WHERE date = '$currentDate' AND user_id = '$userID'");
$merr = mysqli_error($link);
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  $query = mysqli_query($link, "UPDATE ADD_TIME SET STOPTIME = '$currentTime' WHERE id = '$pauseID'");

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