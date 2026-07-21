<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$ID = request_post_int('alertID');
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
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
} 
echo $ID;                         
?>
