<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$ID = request_post_int('addID');
$DESC = request_post_string('suDesc');
$ACCEPTMODE = request_post_int('accept');
$acceptorID = (int) $_SESSION['ss_id'];

if (!in_array($ACCEPTMODE, array(-1, 1), true)) {
  deny_ajax_access(400, 'INVALID_MODE');
}

require_ajax_delay_supervisor($ID, 3);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

if (!mysqli_begin_transaction($link)) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$delayResult = db_query(
  $link,
  'SELECT userID, date, penaltyID FROM Delays WHERE ID = ? LIMIT 1 FOR UPDATE',
  'i',
  array($ID)
);

if (!$delayResult || !($delayRow = mysqli_fetch_assoc($delayResult))) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$getUserID = (int) $delayRow['userID'];
$PENALTYDATE = (string) $delayRow['date'];
$storedPenaltyID = (int) $delayRow['penaltyID'];
$PENALTYID = $storedPenaltyID > 0 ? $storedPenaltyID : -1;

$newPenID = -1;
$errorThere = 0;

if ( $ACCEPTMODE == -1 )
{
  if ( $PENALTYID == -1 )
  {
    $lastPenaltyResult = db_query($link, 'SELECT ID FROM Penalty ORDER BY ID DESC LIMIT 1 FOR UPDATE');

    if (!$lastPenaltyResult)
    {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      $errorThere = 1;
    }
    else
    {
      $lastPenalty = mysqli_fetch_assoc($lastPenaltyResult);
      $newPenID = $lastPenalty ? (int) $lastPenalty['ID'] + 1 : 1;
      $query = db_execute($link, 'INSERT INTO Penalty VALUES (?, ?, ?, ?, ?)', 'siiis', array($PENALTYDATE, $newPenID, $getUserID, $acceptorID, $DESC));

      if ( !$query )
      {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        $errorThere = 1;
      }
    }
  }
  else
  {
    $query = db_execute($link, 'UPDATE Penalty SET date = ?, supervisorID = ?, reason = ? WHERE ID = ? AND userID = ?', 'sisii', array($PENALTYDATE, $acceptorID, $DESC, $PENALTYID, $getUserID));
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
    $query = db_execute($link, 'DELETE FROM Penalty WHERE ID = ? AND userID = ?', 'ii', array($PENALTYID, $getUserID));
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
  $query = db_execute($link, 'UPDATE Delays SET acceptorID = ?, penaltyReply = ?, status = ?, penaltyID = ? WHERE ID = ? AND userID = ?', 'isiiii', array($acceptorID, $DESC, $ACCEPTMODE, $newPenID, $ID, $getUserID));
  if ( !$query )
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    $errorThere = 1;
  }
}

if ($errorThere == 0)
{
  if (!mysqli_commit($link)) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    mysqli_rollback($link);
  }
}
else
{
  mysqli_rollback($link);
}
?>
