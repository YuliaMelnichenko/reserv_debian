<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$ID = $_POST['addID'];
$DESC = $_POST['suDesc'];
$ACCEPTMODE = $_POST['accept'];
$userID = $_SESSION['ss_id']; 

include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");
$query = mysqli_query($link, "UPDATE Delays SET acceptorID = '$userID', penaltyReply = '$DESC', status='$ACCEPTMODE' WHERE ID = '$ID'"); 

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo "<br>mysql_error = $merr<br>";
} 
?>