<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$userID = $_SESSION['ss_id']; 
$currentDate = date('Y-m-d');
$currentTime = date("H:i:s");
$pauseID = $_POST['pauseID'];

include_once"../../php_tori/connect.php";

$query = mysql_query("update visiting set take_pause = '0' where date = '$currentDate' and user_id = '$userID'");
$merr=mysql_error();
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  $query = mysql_query("update ADD_TIME set STOPTIME = '$currentTime' where id = '$pauseID'");

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


                                                                         