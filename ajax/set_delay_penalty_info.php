<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$ID = request_post_int('addID');
$DESC = request_post_string('suDesc');
$ACCEPTMODE = request_post_int('accept');
$acceptorID = (int) $_SESSION['ss_id'];

if (!in_array($ACCEPTMODE, array(-1, 1), true)) {
  deny_ajax_access(400, 'INVALID_MODE');
}

require_ajax_delay_supervisor($ID, 3);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . '/../inc/notification_decision_service.php';

db_set_charset($link, "utf8");
$query = notification_decision_update_delay($link, $ID, $acceptorID, $DESC, $ACCEPTMODE);

if (!$query) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
}
?>
