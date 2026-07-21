<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();

ajax_json_headers();

include_once __DIR__ . '/../funcs.php';
include __DIR__ . '/../php_tori/connect.php';

mysqli_set_charset($link, 'utf8');

$userID = (int)$_SESSION['ss_id'];
$oldStartDTStr = isset($_SESSION['ss_startDTStr']) ? (string)$_SESSION['ss_startDTStr'] : '';
$oldStopDTStr = isset($_SESSION['ss_stopDTStr']) ? (string)$_SESSION['ss_stopDTStr'] : '';
$oldDefaultStartTimeWithDelayVal = isset($_SESSION['ss_defaultStartTimeWithDelayVal'])
  ? (int)$_SESSION['ss_defaultStartTimeWithDelayVal']
  : 0;

$timeArrNow = get_current_datetime_in_timezone();
$currentDateTime = $timeArrNow[1];

$userDayTransitionTime = isset($_SESSION['ss_dayTransitionTime'])
  ? $_SESSION['ss_dayTransitionTime']
  : get_standard_day_transition_time();

$dateArr = datetimestr_to_day_start_stop_DT_ex_str($currentDateTime, $userDayTransitionTime);
$startDTStr = $dateArr[0];
$stopDTStr = $dateArr[1];

$userDefaultStartTime = '';
$userAllowedDelay = 0;
ob_start();
get_user_defStartTime_and_allowedDelay($userID, $userDefaultStartTime, $userAllowedDelay);
$startSettingsOutput = trim(ob_get_clean());

if ($startSettingsOutput !== '') {
  http_response_code(500);
  ajax_json_response(array(
    'valid' => 0,
    'error' => $startSettingsOutput
  ));
  exit;
}

if ($userDefaultStartTime == '' || $userDefaultStartTime == 'NDF') {
  $userDefaultStartTime = isset($_SESSION['ss_defaultStartTime'])
    ? $_SESSION['ss_defaultStartTime']
    : 'NDF';
}

$userAllowedDelay = (int)$userAllowedDelay;
$defaultStartTimeWithDelay = 'NDF';
$defaultStartTimeWithDelayVal = 0;

if ($userDefaultStartTime != 'NDF' && strtotime($userDefaultStartTime) !== false) {
  $defaultStartTimeWithDelay = date(
    'H:i:s',
    strtotime($userDefaultStartTime . ' + ' . $userAllowedDelay . ' minute')
  );
  $defaultStartTimeWithDelayVal = strtotime($defaultStartTimeWithDelay);
}

$_SESSION['ss_defaultStartTime'] = $userDefaultStartTime;
$_SESSION['ss_allowedDelay'] = $userAllowedDelay;
$_SESSION['ss_defaultStartTimeWithDelay'] = $defaultStartTimeWithDelay;
$_SESSION['ss_defaultStartTimeWithDelayVal'] = $defaultStartTimeWithDelayVal;

ob_start();
sync_time_registration_session_by_period($link, $userID, $startDTStr, $stopDTStr);
$syncOutput = trim(ob_get_clean());

if ($syncOutput !== '') {
  http_response_code(500);
  ajax_json_response(array(
    'valid' => 0,
    'error' => $syncOutput
  ));
  exit;
}

$periodChanged = $oldStartDTStr !== $startDTStr || $oldStopDTStr !== $stopDTStr;
$startSettingsChanged = $oldDefaultStartTimeWithDelayVal !== $defaultStartTimeWithDelayVal;

ajax_json_response(array(
  'valid' => 1,
  'periodChanged' => $periodChanged ? 1 : 0,
  'startSettingsChanged' => $startSettingsChanged ? 1 : 0,
  'refreshTimeRegistration' => ($periodChanged || $startSettingsChanged) ? 1 : 0,
  'state' => isset($_SESSION['ss_state']) ? (int)$_SESSION['ss_state'] : 1,
  'visitingID' => isset($_SESSION['ss_visiting_ID']) ? (int)$_SESSION['ss_visiting_ID'] : 0,
  'startDTStr' => $startDTStr,
  'stopDTStr' => $stopDTStr
));
?>
