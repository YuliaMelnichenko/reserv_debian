<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

if ( isset( $_POST['userID'] ) )
{
  $userId = $_POST['userID']; 
}
else
{
  $userId = $_SESSION['ss_id']; 
}

$ss_delay_duration = $_SESSION['ss_delay_duration'];

include_once "../funcs.php";
include_once"../../php_tori/connect.php";

$currentDateArr = get_current_datetime_in_timezone();
$currentDate = $currentDateArr[2];

mysql_query( 'SET NAMES utf8' ); 

$query = mysql_query("select * from Delays where userID = '$userId' and date = '$currentDate'");
$merr=mysql_error();
if ( !$query ) 
{
  echo "<br>mysql_error = $merr<br>";
  $errorThere = 1;
}

$vn=mysql_num_rows($query);

if ( $vn == 0 )
{
  $newID = 0;

  $query = mysql_query("SELECT max(ID) FROM Delays"); 
  $merr=mysql_error();
  if ( !$query ) 
  {
    echo "<br>mysql_error = $merr<br>";
  }
  else if ( $row = mysql_fetch_array($query) )
  {
    $newID = $row[0] + 1;
  }

  $query = mysql_query("insert into Delays VALUES ('$newID', '$currentDate', '$ss_delay_duration', '$userId', '-1', 'Без объяснения', '-1', '-1', '', '0')");
  $merr=mysql_error();
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
                   
                                                                         