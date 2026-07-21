<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = (int)($_SESSION['ss_id'] ?? 0);
$visitingID = (int)($_SESSION['ss_visiting_ID'] ?? 0);
$pauseID = request_post_int('pauseID');

require_ajax_add_time_access($pauseID);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . "/../inc/pause_service.php";

$result = finish_time_pause(
  $link,
  $userID,
  $visitingID,
  $pauseID,
  get_current_datetime_in_timezone_str(1, 0)
);

if ($result === false) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

if ($result['status'] !== 'success') {
  ajax_text_response($result['message']);
  exit;
}

ajax_text_response('1');
?>
