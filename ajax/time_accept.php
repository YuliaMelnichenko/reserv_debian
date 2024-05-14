<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();
                
$ID = $_POST['recID'];

include_once"../../php_tori/connect.php";

$query = mysql_query("UPDATE ADD_TIME SET APPROVED=1 WHERE ID = '$ID'"); 

$merr=mysql_error();
if ( !$query ) 
{
  echo "<br>mysql_error = $merr<br>";
} 
echo $ID;                         
?>

                                                                         