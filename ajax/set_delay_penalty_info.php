<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_superuser();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$ID = $_POST['addID'];                
$DESC = $_POST['suDesc'];
$ACCEPTMODE = $_POST['accept'];
$PENALTYID = $_POST['penaltyID'];
$PENALTYDATE = $_POST['penDate'];
$getUserID = $_POST['userID']; 
$acceptorID = $_SESSION['ss_id']; 

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

$newPenID = -1;
$errorThere = 0;

if ( $ACCEPTMODE == -1 )
{
  if ( $PENALTYID == -1 )
  {
    $newPenID = get_penalty_id();  
    $query = mysqli_query($link, "INSERT INTO Penalty VALUES ( '$PENALTYDATE', '$newPenID', '$getUserID', '$acceptorID', '$DESC' )" );
    $merr=mysqli_error($link);
    if ( !$query ) 
    {
      echo "<br>mysql_error = $merr<br>";
      $errorThere = 1;
    }
  }
  else
  {
    $query = mysqli_query($link, "UPDATE Penalty SET date = '$PENALTYDATE', supervisorID = '$acceptorID', reason = '$DESC' WHERE ID = '$PENALTYID'" );
    $merr=mysqli_error($link);
    if ( !$query ) 
    {
      echo "<br>mysql_error = $merr<br>";
      $errorThere = 1;
    }
    $newPenID = $PENALTYID;
  }
}
else
{
  if ( $PENALTYID != -1 )
  {
    $query = mysqli_query($link, "DELETE FROM Penalty WHERE ID = '$PENALTYID' ");
    $merr=mysqli_error($link);
    if ( !$query ) 
    {
      echo "<br>mysql_error = $merr<br>";
      $errorThere = 1;
    }
    $newPenID = -1;
  }  
}
if ( $errorThere == 0 )
{ 
  $query = mysqli_query($link, "UPDATE Delays SET acceptorID = '$acceptorID', penaltyReply = '$DESC', status = '$ACCEPTMODE', penaltyID = '$newPenID' WHERE ID = '$ID'"); 
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo "<br>mysql_error = $merr<br>";
  }
}
?>