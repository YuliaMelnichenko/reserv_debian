<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$userID_ = $_SESSION['ss_id'];   

$ta_start_date_time = $_POST['ta_start_date_time'];
$ta_stop_date_time = $_POST['ta_stop_date_time'];
$ta_base = $_POST['ta_base'];
$ta_desc = $_POST['ta_desc'];


$ta_start_date = substr( $ta_start_date_time, 0, 10 );
$ta_stop_date = substr( $ta_stop_date_time, 0, 10 );
$ta_start_time = substr( $ta_start_date_time, 11, 8 );
$ta_stop_time = substr( $ta_stop_date_time, 11, 8 );

include_once"../../php_tori/connect.php";

$query0 = mysql_query("SELECT * FROM ADD_TIME"); 

$newID = 0;

$merr=mysql_error();
if ( !$query0 ) 
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  $vn=mysql_num_rows($query0);
  $newID = $vn + 1;
}

mysql_query( 'SET NAMES utf8' ); 
$query = mysql_query("insert into ADD_TIME (ID, USERID, STARTDATE, STOPDATE, STARTTIME, STOPTIME, REASON, DESCRIPTION, APPROVED) VALUES ('$newID','$userID_','$ta_start_date','$ta_stop_date','$ta_start_time','$ta_stop_time','$ta_base','$ta_desc','0')");
$merr=mysql_error();
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
?>

                                                                         