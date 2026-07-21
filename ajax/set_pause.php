<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";
require_once __DIR__ . "/../inc/pause_service.php";

mysqli_set_charset($link, "utf8");

$userID = (int)($_SESSION['ss_id'] ?? 0);
$visitingID = (int)($_SESSION['ss_visiting_ID'] ?? 0);
$superUserID = request_post_int('superuserID', -1);
$description = request_post_string('desk');

if ($superUserID <= 0) {
  deny_ajax_access(400, 'INVALID_SUPERVISOR');
}

$dateTime = get_current_datetime_in_timezone();
$result = start_time_pause(
  $link,
  $userID,
  $visitingID,
  $superUserID,
  $dateTime[2],
  $dateTime[1],
  $description
);

if ($result === false) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

if ($result['status'] === 'forbidden') {
  deny_ajax_access(403, $result['message']);
}

if ($result['status'] !== 'success') {
  ajax_text_response($result['message']);
  exit;
}

ajax_text_response('1');
?>
