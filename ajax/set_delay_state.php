<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$ID = request_post_int('addID');
$mode = request_post_int('mode');

if (!in_array($mode, array(100, 200), true)) {
  deny_ajax_access(400, 'INVALID_MODE');
}

require_ajax_delay_supervisor($ID, 3);

include_once __DIR__ . "/../php_tori/connect.php";

if ( $mode == 100 )
{
  $query = db_execute($link, 'UPDATE Delays SET status = status + 100 WHERE ID = ?', 'i', array($ID));
}
else
{
  $query = db_execute($link, 'UPDATE Delays SET status = status - 100 WHERE ID = ?', 'i', array($ID));
}

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
echo $ID;                         
?>
