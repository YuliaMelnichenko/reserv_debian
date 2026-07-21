<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = (int)($_SESSION['ss_id'] ?? 0);
$visitingID = (int)($_SESSION['ss_visiting_ID'] ?? 0);
$description = request_post_string('desk');

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";
require_once __DIR__ . "/../inc/pause_service.php";

mysqli_set_charset($link, "utf8");

$dateTime = get_current_datetime_in_timezone();
$result = start_sport_time_pause(
  $link,
  $userID,
  $visitingID,
  $dateTime[2],
  $dateTime[1],
  $description
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
