<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID_ = (int)$_SESSION['ss_id'];

$ss_delay_duration = (int)$_SESSION['ss_delay_duration'];

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$currentDateArr = get_current_datetime_in_timezone();
$currentDate = $currentDateArr[2];

$superuserID = (int) ($_POST['delayExplanationSU'] ?? -1);
$delayExplanation = (string) ($_POST['delayExplanation'] ?? '');

if ($superuserID !== -1) {
  if ($superuserID <= 0) {
    deny_ajax_access(400, 'INVALID_SUPERVISOR');
  }

  $supervisorQuery = db_query(
    $link,
    'SELECT 1 FROM GROUPS WHERE USERID = ? AND SUPERVISORID = ? LIMIT 1',
    'ii',
    array($userID_, $superuserID)
  );

  if (!$supervisorQuery) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  if (mysqli_num_rows($supervisorQuery) === 0) {
    deny_ajax_access(403, 'FORBIDDEN_SUPERVISOR');
  }
}

$mode = 0;

if ( isset( $_POST['mode'] ) AND $_POST['mode'] == 1 )
{
  $mode = (int) $_POST['mode'];
  $delayID = (int) ($_POST['delayID'] ?? 0);
}

if (!mysqli_begin_transaction($link)) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$idQuery = db_query($link, 'SELECT ID FROM Delays ORDER BY ID DESC LIMIT 1 FOR UPDATE');

if (!$idQuery) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$lastDelay = mysqli_fetch_assoc($idQuery);

if ($mode == 0)
{
  $query0 = db_query(
    $link,
    'SELECT ID, STATUS FROM Delays WHERE date = ? AND userID = ? ORDER BY ID DESC LIMIT 1 FOR UPDATE',
    'si',
    array($currentDate, $userID_)
  );
}
else
{
  $query0 = db_query(
    $link,
    'SELECT ID, STATUS FROM Delays WHERE ID = ? AND userID = ? FOR UPDATE',
    'ii',
    array($delayID, $userID_)
  );
}

if (!$query0) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$delay = mysqli_fetch_assoc($query0);

if (!$delay)
{
  $newID = $lastDelay ? (int)$lastDelay['ID'] + 1 : 1;
  $query = db_execute(
    $link,
    "INSERT INTO Delays VALUES (?, ?, ?, ?, ?, ?, -1, -1, '', 0)",
    'isiiis',
    array($newID, $currentDate, $ss_delay_duration, $userID_, $superuserID, $delayExplanation)
  );

  if (!$query)
  {
    mysqli_rollback($link);
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  $response = "1";
}
else
{
  $newID = (int)$delay['ID'];
  $status = (int)$delay['STATUS'];

  if ($status == 0)
  {
    if ($mode == 0)
    {
      $query = db_execute($link, 'UPDATE Delays SET supervisorID = ?, explaneDesk = ? WHERE id = ?', 'isi', array($superuserID, $delayExplanation, $newID));
    }
    else
    {
      $query = db_execute($link, 'UPDATE Delays SET supervisorID = ?, explaneDesk = ? WHERE ID = ? AND userID = ?', 'isii', array($superuserID, $delayExplanation, $delayID, $userID_));
    }

    if (!$query)
    {
      mysqli_rollback($link);
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    $response = "2";
  }
  else
  {
    $response = "5550 $status";
  }
}

if (!mysqli_commit($link)) {
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

echo $response;
?>
