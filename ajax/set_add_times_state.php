<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$ID = (int) ($_POST['addID'] ?? 0);
$mode = (int) ($_POST['mode'] ?? 0);

if (!in_array($mode, array(100, 200), true)) {
  deny_ajax_access(400, 'INVALID_MODE');
}

require_ajax_add_time_supervisor($ID, 0);

include_once __DIR__ . "/../php_tori/connect.php";

if ( $mode == 100 )
{
  $query = db_execute($link, 'UPDATE ADD_TIME SET APPROVED = APPROVED + 100 WHERE ID = ?', 'i', array($ID));
}
else
{
  $query = db_execute($link, 'UPDATE ADD_TIME SET APPROVED = APPROVED - 100 WHERE ID = ?', 'i', array($ID));
}

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
echo $ID;                         
?>
