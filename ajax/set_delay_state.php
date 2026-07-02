<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$ID = $_POST['addID'];
$mode = $_POST['mode'];

include_once __DIR__ . "/../php_tori/connect.php";

if ( $mode == 100 )
{
  $query = mysqli_query($link, "UPDATE Delays  SET status=status + 100 WHERE ID = '$ID'"); 
}
else if ( $mode == 200 )
{
  $query = mysqli_query($link, "UPDATE Delays  SET status=status - 100 WHERE ID = '$ID'"); 
}

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo "<br>mysql_error = $merr<br>";
} 
echo $ID;                         
?>