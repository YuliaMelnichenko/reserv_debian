<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = request_post_int('userID');
$newInTime = request_post_time('inTime');

if ($userID <= 0) {
  deny_ajax_access(400, 'INVALID_USER');
}

if ($newInTime === null) {
  echo "-13";
  exit;
}

require_ajax_supervisor_for_user($userID, 3);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . "/../inc/entrance_adjustment.php";

$currentDateTime = get_current_datetime_in_timezone()[1];
$dateRange = datetimestr_to_day_start_stop_DT_ex_str(
  $currentDateTime,
  isset($_SESSION['ss_dayTransitionTime']) ? $_SESSION['ss_dayTransitionTime'] : '00:00:00'
);
$startDTStr = $dateRange[0];
$stopDTStr = $dateRange[1];

$transaction = db_transaction_start($link);

if (!$transaction) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

$query = db_query($link, "
  SELECT ID, in_dt, out_dt, eat_start_dt, eat_stop_dt, state
  FROM visiting
  WHERE user_id = ?
    AND in_dt >= ?
    AND in_dt <= ?
  ORDER BY in_dt DESC, ID DESC
  LIMIT 1
  FOR UPDATE
", 'iss', array($userID, $startDTStr, $stopDTStr));

if (!$query) {
  $transaction->rollback();
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

$visitRow = mysqli_fetch_assoc($query);

if (!$visitRow) {
  $transaction->rollback();
  echo "0";
  exit;
}

$adjustment = build_entrance_adjustment($visitRow, $newInTime);
$resultCode = (int)$adjustment['code'];

if ($resultCode <= 0) {
  $transaction->rollback();
  echo (string)$resultCode;
  exit;
}

$updated = db_execute($link, "
  UPDATE visiting
  SET in_dt = ?,
      out_dt = ?,
      eat_start_dt = ?,
      eat_stop_dt = ?,
      adj = 1
  WHERE ID = ?
    AND user_id = ?
", 'ssssii', array(
  $adjustment['in_dt'],
  $adjustment['out_dt'],
  $adjustment['eat_start_dt'],
  $adjustment['eat_stop_dt'],
  (int)$visitRow['ID'],
  $userID
));

if (!$updated) {
  $transaction->rollback();
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

if (!$transaction->commit()) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

echo (string)$resultCode;
?>
