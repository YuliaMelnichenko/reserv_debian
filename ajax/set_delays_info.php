<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_superuser();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$ID = (int) ($_POST['addID'] ?? 0);
$DESC = (string) ($_POST['suDesc'] ?? '');
$ACCEPTMODE = (int) ($_POST['accept'] ?? 0);
$userID = (int) $_SESSION['ss_id'];

include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");
$query = db_execute(
  $link,
  'UPDATE Delays SET acceptorID = ?, penaltyReply = ?, status = ? WHERE ID = ?',
  'isii',
  array($userID, $DESC, $ACCEPTMODE, $ID)
);

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
?>
