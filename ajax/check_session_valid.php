<?php
require_once __DIR__ . '/../inc/session.php';
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if (
  !isset($_SESSION['ss_sessid']) ||
  !isset($_SESSION['ss_id']) ||
  !isset($_SESSION['ss_startDTStr']) ||
  !isset($_SESSION['ss_stopDTStr'])
) {
  echo "0";
  return;
}

include_once __DIR__ . "/../funcs.php";

$startDTStr = $_SESSION['ss_startDTStr'];
$stopDTStr = $_SESSION['ss_stopDTStr'];

$startDTStrVal = strtotime($startDTStr);
$stopDTStrVal = strtotime($stopDTStr);

$timeArrNow = get_current_datetime_in_timezone();
$dateTime = $timeArrNow[1];
$dateTimeVal = strtotime($dateTime);

$currentDate = get_current_datetime_in_timezone_str(1, 0);

$user_dayTransitionTime = isset($_SESSION['ss_dayTransitionTime'])
  ? $_SESSION['ss_dayTransitionTime']
  : "00:00:00";

$timeArr = datetimestr_to_day_start_stop_DT_ex_str($currentDate, $user_dayTransitionTime);

$startDTCalcStr = $timeArr[0];
$stopDTCalcStr = $timeArr[1];

$startDTCalcVal = strtotime($startDTCalcStr);
$stopDTCalcVal = strtotime($stopDTCalcStr);

if ($startDTStrVal === false || $stopDTStrVal === false || $dateTimeVal === false || $startDTCalcVal === false || $stopDTCalcVal === false) {
  echo "0";
  return;
}

if ($startDTStrVal == $startDTCalcVal && $stopDTStrVal == $stopDTCalcVal) {
  if ($dateTimeVal >= $startDTStrVal && $dateTimeVal <= $stopDTStrVal) {
    $ss_defaultStartTime = isset($_SESSION['ss_defaultStartTime'])
      ? $_SESSION['ss_defaultStartTime']
      : "00:00:00";

    $ss_allowedDelay = isset($_SESSION['ss_allowedDelay'])
      ? (int)$_SESSION['ss_allowedDelay']
      : 0;

    $ss_defaultStartTimeWithDelayValCalc = strtotime(date("H:i:s", strtotime($ss_defaultStartTime . " + " . $ss_allowedDelay . " minute")));

    $ss_defaultStartTimeWithDelayValExist = isset($_SESSION['ss_defaultStartTimeWithDelayVal'])
      ? $_SESSION['ss_defaultStartTimeWithDelayVal']
      : 0;

    if ($ss_defaultStartTimeWithDelayValCalc == $ss_defaultStartTimeWithDelayValExist) {
      echo "1";
      return;
    }

    $_SESSION['ss_defaultStartTimeWithDelayVal'] = $ss_defaultStartTimeWithDelayValCalc;

    $ssState = isset($_SESSION['ss_state'])
      ? (int)$_SESSION['ss_state']
      : 1;

    if ($ssState != 0) {
      $differTimeSec = $dateTimeVal - $startDTCalcVal;

      if ($differTimeSec >= 0 && $differTimeSec < 3600 * 3) {
        $_SESSION['ss_dayWasChanged'] = 1;
        echo "0";
        return;
      }
    }

    echo "0";
    return;
  }

  echo "0";
  return;
}

$ssState = isset($_SESSION['ss_state'])
  ? (int)$_SESSION['ss_state']
  : 1;

if ($ssState != 0 && $ssState != 1) {
  $differTimeSec = $dateTimeVal - $startDTCalcVal;

  if ($differTimeSec >= 0 && $differTimeSec < 3600 * 3) {
    $_SESSION['ss_dayWasChanged'] = 1;
    echo "0";
    return;
  }

  echo "1";
  return;
}

echo "1";
return;
?>