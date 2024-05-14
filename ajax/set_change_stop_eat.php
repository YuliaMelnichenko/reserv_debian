<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();
$userID = $_SESSION['ss_id'];
$currentDate = date('Y-m-d H:i:s');
$dayNumber = $_POST['currentDayNumber'];
$new_stop_eat_time = $_POST['add_stop_eat_time'];

include_once "../../php_tori/connect.php";
mysql_query( 'SET NAMES utf8' ); 

if ($dayNumber == "1") {
  $res = mysql_query("UPDATE visiting SET eat_stop_dt = '$new_stop_eat_time', state = 4, changes = 1 WHERE user_id = '$userID' and DATE(in_dt) = DATE(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 3 DAY))");
  $merr=mysql_error();
}
else {
  $res = mysql_query("UPDATE visiting SET eat_stop_dt = '$new_stop_eat_time', state = 4, changes = 1 WHERE user_id = '$userID' and DATE(in_dt) = DATE(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY))");
  $merr=mysql_error();
}

if ( !$res ) 
{
  echo "<br>mysql_error = $merr<br>";
} 
else {
  echo "2";
}

?>