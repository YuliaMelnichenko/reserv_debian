<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

  mysqli_set_charset($link, "utf8");

$userID = $_SESSION['ss_id'];
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];
 
$superUserID = $_POST['superuserID'];
$description = $_POST['desk'];

$dtResult = get_current_datetime_in_timezone();

$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];

include_once "/var/www/tori/php_tori/connect.php";

$query = mysqli_query($link, "update visiting set take_pause = '1' where id = '$ss_visiting_ID' and user_id = '$userID'");
$merr=mysqli_error($link);
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  mysqli_set_charset($link, "utf8");
  
  $query = mysqli_query($link, "insert into ADD_TIME (ADDDATE,        SUIR,           USERID,    START_DT,           REASON, DESCRIPTION,   SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT ) 
                        VALUES              ('$currentDate', '$superUserID', '$userID', '$currentDateTime', '-1',   '$description', '',            '0',      '1',        '0')");

  $merr=mysqli_error($link);
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