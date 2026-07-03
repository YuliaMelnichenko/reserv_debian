<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$ID = (int) ($_POST['alertID'] ?? 0);
$userID = (int) $_SESSION['ss_id'];

include_once __DIR__ . "/../php_tori/connect.php";

$query = db_execute(
  $link,
  'UPDATE ALERTS SET VIEWED = 1 WHERE ID = ? AND USERID = ?',
  'ii',
  array($ID, $userID)
);

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
echo $ID;                         
?>
