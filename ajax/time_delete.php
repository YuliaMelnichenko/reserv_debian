<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$delID = request_post_int('delID');
require_ajax_add_time_access($delID);

include_once __DIR__ . "/../php_tori/connect.php";

$query = db_execute($link, 'DELETE FROM ADD_TIME WHERE ID = ?', 'i', array($delID));

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
echo $delID;                         
?>
