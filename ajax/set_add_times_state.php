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

require_ajax_add_time_supervisor($ID, 0);

include_once __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . '/../inc/notification_decision_service.php';

$query = notification_decision_set_add_time_deleted($link, $ID, $mode);

$merr = db_error($link);
if ( !$query ) 
{
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
} 
echo $ID;                         
?>
