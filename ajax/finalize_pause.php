<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = (int)$_SESSION['ss_id'];
$visitingID = (int)($_SESSION['ss_visiting_ID'] ?? 0);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$pauseQuery = time_journal_query_open_pause($link, $userID);

if (!$pauseQuery) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

$pause = mysqli_fetch_assoc($pauseQuery);

if (!$pause) {
  deny_ajax_access(404, 'OPEN_PAUSE_NOT_FOUND');
}

$pauseID = (int)$pause['ID'];
$currentDateTime = get_current_datetime_in_timezone_str(1, 0);

$transaction = db_transaction_start($link);
if (!$transaction) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

$query = db_execute($link, 'UPDATE visiting SET take_pause = 0 WHERE id = ? AND user_id = ?', 'ii', array($visitingID, $userID));

if (!$query) {
  $transaction->rollback();
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

$query = time_journal_finish_pause($link, $pauseID, $userID, $currentDateTime);

if (!$query) {
  $transaction->rollback();
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

if (!$transaction->commit()) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

ajax_text_response('1');
?>
