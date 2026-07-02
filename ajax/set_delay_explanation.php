<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID_ = $_SESSION['ss_id']; 

$ss_delay_duration = $_SESSION['ss_delay_duration'];

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$currentDateArr = get_current_datetime_in_timezone();
$currentDate = $currentDateArr[2];

$superuserID = (int) ($_POST['delayExplanationSU'] ?? -1);
$delayExplanation = (string) ($_POST['delayExplanation'] ?? '');

$mode = 0;

if ( isset( $_POST['mode'] ) AND $_POST['mode'] == 1 )
{
  $mode = (int) $_POST['mode'];
  $delayID = (int) ($_POST['delayID'] ?? 0);
}

if ( $mode == 0 )
{
  $query0 = db_query($link, 'SELECT ID, STATUS FROM Delays WHERE date = ? AND userID = ?', 'si', array($currentDate, $userID_));
}
else
{
  $query0 = db_query($link, 'SELECT ID, STATUS FROM Delays WHERE ID = ? AND userID = ?', 'ii', array($delayID, $userID_));
}

$insertMode = 1;
$status = 0;

$newID = 0;

while ( $row0 = mysqli_fetch_array($query0, MYSQLI_ASSOC) )
{  
  $newID = $row0["ID"];
  $status = $row0["STATUS"];
  $insertMode = 0;
}

echo "__ $currentDate\n";

if ( $insertMode == 1 )
{
  $query0 = mysqli_query($link, "SELECT max(ID) FROM Delays"); 
  $merr=mysqli_error($link);
  if ( !$query0 ) 
  {
    echo "<br>mysql_error = $merr<br>";
  }
  else if ( $row = mysqli_fetch_array($query0) )
  {
    $newID = $row[0] + 1;
  }
}

mysqli_set_charset($link, "utf8");
if ( $insertMode == 1 )
{
  $query = db_execute(
    $link,
    "INSERT INTO Delays VALUES (?, ?, ?, ?, ?, ?, -1, -1, '', 0)",
    'isiiis',
    array($newID, $currentDate, $ss_delay_duration, $userID_, $superuserID, $delayExplanation)
  );
  $merr=mysqli_error($link);
  if (!$query)
  {
    echo "<br>mysql_error = $merr<br>";
  }
  else
  {
    echo "1";
  }
}
else
{

  if ( $status == 0 )
  {
    if ( $mode == 0 )
    { 
      $query = db_execute($link, 'UPDATE Delays SET supervisorID = ?, explaneDesk = ? WHERE id = ?', 'isi', array($superuserID, $delayExplanation, $newID));
    }
    else
    {
      $query = db_execute($link, 'UPDATE Delays SET supervisorID = ?, explaneDesk = ? WHERE ID = ? AND userID = ?', 'isii', array($superuserID, $delayExplanation, $delayID, $userID_));
    }

    $merr=mysqli_error($link);
    if (!$query)
    {
      echo "<br>mysql_error = $merr<br>";
    }
    else
    {
      echo "2";
    }
  }
  else
  {
    echo "5550 $status";
  }
}
?>
