<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$ID = $_POST['addID'];                
$DESC = $_POST['suDesc'];
$ACCEPTMODE = $_POST['accept'];
$PENALTYID = $_POST['penaltyID'];
$PENALTYDATE = $_POST['penDate'];
$getUserID = $_POST['userID']; 
$acceptorID = $_SESSION['ss_id']; 

include_once"../../php_tori/connect.php";
include_once "../funcs.php";

mysql_query( 'SET NAMES utf8' ); 

$newPenID = -1;
$errorThere = 0;

if ( $ACCEPTMODE == -1 )
{
  if ( $PENALTYID == -1 )
  {
    $newPenID = get_penalty_id();  
    $query = mysql_query("insert into Penalty values ( '$PENALTYDATE', '$newPenID', '$getUserID', '$acceptorID', '$DESC' )" );
    $merr=mysql_error();
    if ( !$query ) 
    {
      echo "<br>mysql_error = $merr<br>";
      $errorThere = 1;
    }
  }
  else
  {
    $query = mysql_query("update Penalty set date = '$PENALTYDATE', supervisorID = '$acceptorID', reason = '$DESC' where ID = '$PENALTYID'" );
    $merr=mysql_error();
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
    $query = mysql_query("delete from Penalty where ID = '$PENALTYID' ");
    $merr=mysql_error();
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
  $query = mysql_query( "UPDATE Delays SET acceptorID = '$acceptorID', penaltyReply = '$DESC', status = '$ACCEPTMODE', penaltyID = '$newPenID' WHERE ID = '$ID'"); 
  $merr=mysql_error();
  if ( !$query ) 
  {
    echo "<br>mysql_error = $merr<br>";
  }
}
?>
                                   
                                                                         