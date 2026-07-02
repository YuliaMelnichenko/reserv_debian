<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( isset( $_POST['userID'] ) )
{
  $userId = (int) $_POST['userID'];
}
else
{
  $userId = (int) $_SESSION['ss_id'];
}

require_ajax_self_or_superuser($userId);

$ss_delay_duration = $_SESSION['ss_delay_duration'];

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$currentDateArr = get_current_datetime_in_timezone();
$currentDate = $currentDateArr[2];

mysqli_set_charset($link, "utf8");

$query = mysqli_query($link, "SELECT * FROM Delays WHERE userID = '$userId' AND date = '$currentDate'");
$merr=mysqli_error($link);
if ( !$query ) 
{
  echo "<br>mysql_error = $merr<br>";
  $errorThere = 1;
}

$vn=mysqli_num_rows($query);

if ( $vn == 0 )
{
  $newID = 0;

  $query = mysqli_query($link, "SELECT max(ID) FROM Delays"); 
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo "<br>mysql_error = $merr<br>";
  }
  else if ( $row = mysqli_fetch_array($query) )
  {
    $newID = $row[0] + 1;
  }

  $query = mysqli_query($link, "INSERT INTO Delays VALUES ('$newID', '$currentDate', '$ss_delay_duration', '$userId', '-1', 'Без объяснения', '-1', '-1', '', '0')");
  $merr=mysqli_error($link);
  if (!$query)
  {
    echo "<br>mysql_error = $merr<br>";
  }
  else
  {
    echo "insert";
    $_SESSION['ss_ch_delay_ID'] = $newID; 	
  }          
}
else
{
  echo "exist";
}
?>