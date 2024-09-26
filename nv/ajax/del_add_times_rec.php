<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();
                
$delID = $_POST['addID'];

include_once"../../php_tori/connect.php";

$query = mysql_query("DELETE FROM ADD_TIME WHERE ID = '$delID'"); 

$merr=mysql_error();
if ( !$query ) 
{
  echo "<br>mysql_error = $merr<br>";
} 
echo $delID;                         
?>

                                                                         