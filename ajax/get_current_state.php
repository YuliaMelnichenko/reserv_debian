<?php
session_start();

header('Content-Type: text/plain; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['ss_id'])) {
  echo 0;
  exit;
}

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

$userID = $_SESSION['ss_id'];

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

$userID = mysqli_real_escape_string($link, $userID);
$startDTStr = mysqli_real_escape_string($link, $startDTStr);
$stopDTStr = mysqli_real_escape_string($link, $stopDTStr);
$currentDateTime = mysqli_real_escape_string($link, $currentDateTime);

$query = mysqli_query($link, "
  SELECT ID, state, eat_start_dt
  FROM visiting
  WHERE user_id = '$userID'
    AND (
      (
        in_dt >= '$startDTStr'
        AND in_dt < '$stopDTStr'
      )
      OR
      (
        state != 0
        AND in_dt < '$startDTStr'
        AND TIMESTAMPDIFF(SECOND, '$startDTStr', '$currentDateTime') <= $maxOpenShiftSeconds
      )
    )
  ORDER BY in_dt DESC, ID DESC
  LIMIT 1
");

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