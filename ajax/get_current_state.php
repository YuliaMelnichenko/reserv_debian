<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if (!isset($_SESSION['ss_id'])) {
  echo 0;
  exit;
}

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

$userID = (int)$_SESSION['ss_id'];

$userDayTransitionTime = isset($_SESSION['ss_dayTransitionTime'])
  ? $_SESSION['ss_dayTransitionTime']
  : "06:00:00";

$dtResult = get_current_datetime_in_timezone();
$currentDateTime = $dtResult[1];

$dateArr = datetimestr_to_day_start_stop_DT_ex_str_idx($currentDateTime, $userDayTransitionTime);

$startDTStr = $dateArr[0];
$stopDTStr = $dateArr[1];

$maxOpenShiftHours = 3;
$maxOpenShiftSeconds = $maxOpenShiftHours * 60 * 60;

$query = db_query($link, "
  SELECT ID, state, eat_start_dt
  FROM visiting
  WHERE user_id = ?
    AND (
      (
        in_dt >= ?
        AND in_dt < ?
      )
      OR
      (
        state != 0
        AND in_dt < ?
        AND TIMESTAMPDIFF(SECOND, ?, ?) <= ?
      )
    )
  ORDER BY in_dt DESC, ID DESC
  LIMIT 1
 ", 'isssssi', array($userID, $startDTStr, $stopDTStr, $startDTStr, $startDTStr, $currentDateTime, $maxOpenShiftSeconds));

if (!$query) {
  echo 0;
  exit;
}

if (mysqli_num_rows($query) == 0) {
  $_SESSION['ss_state'] = 1;
  $_SESSION['ss_visiting_ID'] = 0;

  echo 1;
  exit;
}

$row = mysqli_fetch_array($query, MYSQLI_ASSOC);

$state = (int)$row["state"];

if ($state == 3 && $row["eat_start_dt"] == "0000-00-00 00:00:00") {
  $state = 2;
}

$_SESSION['ss_state'] = $state;
$_SESSION['ss_visiting_ID'] = (int)$row["ID"];

echo $state;
?>
