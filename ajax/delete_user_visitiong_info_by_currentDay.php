<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( isset($_POST['userID']) )
{
  $userID = (int)($_POST['userID']);
  $currentDate = date('Y-m-d');
  
  $newStartTime = $newInTime; 
  $newEatStartTime = "";
  $newEatStopTime = "";

  include_once __DIR__ . "/../funcs.php";
  include_once __DIR__ . "/../php_tori/connect.php";

  $query = mysqli_query($link, "DELETE FROM visiting WHERE date = '$currentDate' AND user_id = '$userID'"); 
  $merr = mysqli_error($link);
  if ( !$query ) 
  {
    $days_errors[] = "MYSQL : $merr";
  }
  else
  {
    echo "1";
    exit; 
  }  
}
echo "0";
?>