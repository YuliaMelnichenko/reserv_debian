<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

include_once "../funcs.php";

$userID = $_SESSION['ss_id'];
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];
 
$superUserID = $_POST['superuserID'];
$description = $_POST['desk'];

$dtResult = get_current_datetime_in_timezone();

$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];



include_once"../../php_tori/connect.php";

$query = mysql_query("update visiting set take_pause = '1' where id = '$ss_visiting_ID' and user_id = '$userID'");
$merr=mysql_error();
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  mysql_query( 'SET NAMES utf8' ); 
  
  $query = mysql_query("insert into ADD_TIME (ADDDATE,        SUIR,           USERID,    START_DT,           REASON, DESCRIPTION,   SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT ) 
                        VALUES              ('$currentDate', '$superUserID', '$userID', '$currentDateTime', '-1',   '$description', '',            '0',      '1',        '0')");


  $merr=mysql_error();
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


                                                                         