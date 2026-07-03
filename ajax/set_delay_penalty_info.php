<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_superuser();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$ID = (int) ($_POST['addID'] ?? 0);
$DESC = (string) ($_POST['suDesc'] ?? '');
$ACCEPTMODE = (int) ($_POST['accept'] ?? 0);
$PENALTYID = (int) ($_POST['penaltyID'] ?? -1);
$PENALTYDATE = (string) ($_POST['penDate'] ?? '');
$getUserID = (int) ($_POST['userID'] ?? 0);
$acceptorID = (int) $_SESSION['ss_id'];

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
    $query = db_execute($link, 'INSERT INTO Penalty VALUES (?, ?, ?, ?, ?)', 'siiis', array($PENALTYDATE, $newPenID, $getUserID, $acceptorID, $DESC));
    $merr=mysqli_error($link);
    if ( !$query ) 
    {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      $errorThere = 1;
    }
  }
  else
  {
    $query = db_execute($link, 'UPDATE Penalty SET date = ?, supervisorID = ?, reason = ? WHERE ID = ?', 'sisi', array($PENALTYDATE, $acceptorID, $DESC, $PENALTYID));
    $merr=mysqli_error($link);
    if ( !$query ) 
    {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      $errorThere = 1;
    }
    $newPenID = $PENALTYID;
  }
}
else
{
  if ( $PENALTYID != -1 )
  {
    $query = db_execute($link, 'DELETE FROM Penalty WHERE ID = ?', 'i', array($PENALTYID));
    $merr=mysqli_error($link);
    if ( !$query ) 
    {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      $errorThere = 1;
    }
    $newPenID = -1;
  }  
}
if ( $errorThere == 0 )
{ 
  $query = db_execute($link, 'UPDATE Delays SET acceptorID = ?, penaltyReply = ?, status = ?, penaltyID = ? WHERE ID = ?', 'isiii', array($acceptorID, $DESC, $ACCEPTMODE, $newPenID, $ID));
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
}
?>
