<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();
                
$ID = $_POST['addID'];
$DESC = $_POST['suDesc'];
$ACCEPTMODE = $_POST['accept'];
$userID = $_SESSION['ss_id']; 

include_once"../../php_tori/connect.php";

mysql_query( 'SET NAMES utf8' ); 
$query = mysql_query("UPDATE ADD_TIME SET SUIR = '$userID', SUPERVISORDESC = '$DESC', APPROVED='$ACCEPTMODE' WHERE ID = '$ID'"); 

$merr=mysql_error();
if ( !$query ) 
{
  echo "<br>mysql_error = $merr<br>";
} 
?>

                                                                         