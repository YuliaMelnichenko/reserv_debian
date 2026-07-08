<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$ID = (int) ($_POST['addID'] ?? 0);
$DESC = (string) ($_POST['suDesc'] ?? '');
$ACCEPTMODE = (int) ($_POST['accept'] ?? 0);
$userID = (int) $_SESSION['ss_id'];

if (!in_array($ACCEPTMODE, array(-1, 1), true)) {
  deny_ajax_access(400, 'INVALID_MODE');
}

require_ajax_add_time_supervisor($ID, 0);

include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");
$query = db_execute(
  $link,
  'UPDATE ADD_TIME SET SUIR = ?, SUPERVISORDESC = ?, APPROVED = ? WHERE ID = ?',
  'isii',
  array($userID, $DESC, $ACCEPTMODE, $ID)
);

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
?>
