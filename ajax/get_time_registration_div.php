<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
date_default_timezone_set("Asia/Novosibirsk");
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include __DIR__ . "/../php_tori/connect.php";

require_once __DIR__ . "/../inc/time_registration_renderer.php";


$userID = (int)$_SESSION['ss_id'];

$currentDate = get_current_datetime_in_timezone_str( 1, 0 );

$dtResult = get_current_datetime_in_timezone();
$currentDateTime = $dtResult[1];

$user_defaultStartTime = "";
$user_allowedDelay = 0;

get_user_defStartTime_and_allowedDelay(
  $userID,
  $user_defaultStartTime,
  $user_allowedDelay
);

if ($user_defaultStartTime == "" || $user_defaultStartTime == "NDF") {
  $user_defaultStartTime = isset($_SESSION['ss_defaultStartTime'])
    ? $_SESSION['ss_defaultStartTime']
    : "NDF";
}

$user_allowedDelay = (int)$user_allowedDelay;

$user_defaultStartTimeWithDelay = "NDF";
$user_defaultStartTimeWithDelayVal = 0;

if ($user_defaultStartTime != "NDF" && strtotime($user_defaultStartTime) !== false) {
  $user_defaultStartTimeWithDelay = date(
    "H:i:s",
    strtotime($user_defaultStartTime . " + " . $user_allowedDelay . " minute")
  );

  $user_defaultStartTimeWithDelayVal = strtotime($user_defaultStartTimeWithDelay);
}

$_SESSION['ss_defaultStartTime'] = $user_defaultStartTime;
$_SESSION['ss_allowedDelay'] = $user_allowedDelay;
$_SESSION['ss_defaultStartTimeWithDelay'] = $user_defaultStartTimeWithDelay;
$_SESSION['ss_defaultStartTimeWithDelayVal'] = $user_defaultStartTimeWithDelayVal;

$user_dayTransitionTime = isset($_SESSION['ss_dayTransitionTime']) ? $_SESSION['ss_dayTransitionTime'] : "06:00:00";
$user_remoteWork = $_SESSION['ss_RemoteWork'];
$visiting_id = isset($_SESSION['ss_visiting_ID'])
  ? (int)$_SESSION['ss_visiting_ID']
  : 0;
$isThereDelayVal = isset($_SESSION['ss_there_is_delay'])
  ? $_SESSION['ss_there_is_delay']
  : 0;

$dateArr = datetimestr_to_day_start_stop_DT_ex_str( $currentDate, $user_dayTransitionTime );

$startDTStr = $dateArr[0];
$stopDTStr = $dateArr[1];

$maxOpenShiftHours = 3;
$maxOpenShiftSeconds = $maxOpenShiftHours * 60 * 60;

$query = db_query($link, "
  SELECT *
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
", 'isssssi', array(
  $userID,
  $startDTStr,
  $stopDTStr,
  $startDTStr,
  $startDTStr,
  $currentDateTime,
  $maxOpenShiftSeconds
));

if (!$query) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

$btnWidth = 616;
$btnHeight = 40;

$vn=mysqli_num_rows($query);
if ( $vn == 0 ) {
  $_SESSION['ss_state'] = 1;
  $_SESSION['ss_visiting_ID'] = 0;

  $dtArr = get_splited_current_date_time_in_timezone();

  $currentTimeHHMMSS = $dtArr[1];

  echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
  echo "<tr>";

  $retArr = get_and_update_start_time_status( $userID );

  $isThereDelay_ = $retArr[0];
  $user_defaultStartTimeWithDelayVal = $retArr[4];
  $user_remoteWork = $retArr[5];

  $currentDelayArr = get_delay_value($currentDateTime, $user_defaultStartTime, $user_allowedDelay);
  $currentDelayVal = $currentDelayArr[1];

  if ( $currentDelayArr[0] != 1 || $user_remoteWork == 1 ) {
    $_SESSION['ss_there_is_delay'] = 0;
    $_SESSION['ss_delay_show_save'] = 0;
    $_SESSION['ss_delay_duration_val'] = 0;
    $_SESSION['ss_delay_duration'] = 0;

    echo "<td>";
      echo "<button id=\"reg_in_work_button\" style=\"font-size: 110%; width:".$btnWidth."px; height:".$btnHeight."px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"reg_in_work();\">Зарегистрировать время прихода</button>";
    echo "</td>";
  }
  else {
    $delayDuration = $currentDelayVal;
    $delayDurationStr = gmdate( "H:i:s", $delayDuration );

    $_SESSION['ss_there_is_delay'] = 2;
    $_SESSION['ss_delay_show_save'] = 1;
    $_SESSION['ss_delay_duration_val'] = $delayDuration;
    $_SESSION['ss_delay_duration'] = $delayDuration;

    echo "<td>";
      echo "<button style=\"font-size: 110%; width:".$btnWidth."px; height:".$btnHeight."px; background-color:#f79398; border:1px solid #888888;\" onclick=\"reg_in_work_with_delay();\">Зарегистрировать приход с опозданием!</button>";
    echo "</td>";
  }

  echo "</tr>";
  echo "</table>";
}
else {
  $row1 = mysqli_fetch_assoc($query);

  $in_dt = $row1["in_dt"];
  $eat_start_dt = $row1["eat_start_dt"];
  $eat_stop_dt = $row1["eat_stop_dt"];
  $out_dt = $row1["out_dt"];
  $state_db = (int)$row1["state"];
  $visitID = (int)$row1["ID"];
  $inDelayArr = get_delay_value($in_dt, $user_defaultStartTime, $user_allowedDelay);
  $isThereDelayVal = $inDelayArr[0] == 1 ? 2 : 0;
  $_SESSION['ss_there_is_delay'] = $isThereDelayVal;
  $_SESSION['ss_delay_show_save'] = $isThereDelayVal == 2 ? 1 : 0;
  $_SESSION['ss_delay_duration_val'] = $inDelayArr[1];
  $_SESSION['ss_delay_duration'] = $inDelayArr[1];

  if ($state_db == 3 && $eat_start_dt == "0000-00-00 00:00:00") {
    db_execute($link, "
      UPDATE visiting
      SET state = 2,
          eat_start_dt = '0000-00-00 00:00:00',
          eat_stop_dt = '0000-00-00 00:00:00',
          changes = 1
      WHERE ID = ?
        AND user_id = ?
    ", 'ii', array($visitID, $userID));

    $state_db = 2;
  }

  if ($state_db == 4 && $eat_start_dt == "0000-00-00 00:00:00") {
    db_execute($link, "
      UPDATE visiting
      SET state = 2,
          eat_start_dt = '0000-00-00 00:00:00',
          eat_stop_dt = '0000-00-00 00:00:00',
          changes = 1
      WHERE ID = ?
        AND user_id = ?
    ", 'ii', array($visitID, $userID));

    $state_db = 2;
  }

  $_SESSION['ss_state'] = $state_db;
  $state = $state_db;
  $state_db = $state;
  $_SESSION['ss_visiting_ID'] = $visitID;

  $changesArr = is_there_day_change( $in_dt, $eat_start_dt, $eat_stop_dt, $out_dt, $currentDate, $state );

  $changeIn = $changesArr[0];
  $changeEatStart = $changesArr[1];
  $changeEatStop = $changesArr[2];
  $changeOut = $changesArr[3];

  // $currentTimeHHMMSS = strtotime( $day_work_start );

  echo "<table border=0>";
  echo "<tr><td>";

  $userRate = get_user_rate( $userID );
  $userDayNormSec = ( $userRate/5 ) * 60 * 60;
  $userDayNormSec = format_time_d_hhmmss_pure( $userDayNormSec );

  $eatNorm = "01:00:00";

  $addTimesArray = get_add_work_info_by_user_and_day_ex( $userID, $startDTStr, $stopDTStr, 1 );

  $currentDay = 1;
  if ( $state == 0 ){ $currentDay = 0; }

  $errorDur = 0;

  $durations = get_durations( $in_dt, $out_dt, $eat_start_dt, $eat_stop_dt, $addTimesArray, $state, $currentDay );

  $resultPureDurationWOEat = $durations[0];
  $resultPureDuration = $durations[3];

  $addTimeDuration = $durations[2];
  $pauseTimeDuration = $durations[5];

  $lunchDuration = $durations[1];

  $resultPureDurationWOEatStr = format_time_d_hhmmss_pure( $resultPureDurationWOEat );
  $resultPureDurationStr = format_time_d_hhmmss_pure( $resultPureDuration );
  $addWorkDurationStr = format_time_d_hhmmss_pure( $addTimeDuration );
  $pauseWorkDurationStr = format_time_d_hhmmss_pure( $pauseTimeDuration );
  $eatDurationStr = format_time_d_hhmmss_pure( $lunchDuration );

  $delayRets = get_delay_info_by_user_and_day( $userID, $currentDate, $user_defaultStartTime, $user_allowedDelay );
  $delayVal = 0;

  if (!empty($delayRets)) {
    $delayVal = $delayRets[0][7];
  }

  $delayStr = format_time_d_hhmmss_pure( $delayVal);

  $timeRestribution = "";
  $timeManagement = "";

  echo "<div id=\"state_buttons\">";
    echo "<div class=\"left_button\">";
      echo "<button id =\"time_back\" title=\"возврат состояния регистрации времени до предыдущего\" style=\"font-size: 100%; width:40px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"rollback_state();\"><img src=\"img/rollbackState.png\"></button>";
    echo "</div>";
    echo "<div class=\"nopadding_s\" align=\"right\" width=50% style=\"font-size: 100%; margin:0; padding:0; margin-left:0;\">";
      if ($state == 3 || $state == 0) {
        echo "<div class=\"right_button\">";
        echo "<button class=\"pauseBtn_des\" title=\"в обеденное время и при отметке об уходе с рабочего места изменения запрещены!\" style=\"font-size: 100%; width:40px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"remote_work();\"><img src=\"img/remoteWorkIcon2.png\" style=\"width: 15px; height: 14px;\"></button>";
        echo "<button class=\"pauseBtn_des\" title=\"в обеденное время и при отметке об уходе с рабочего места приостановка учета времени запрещена!\" style=\"font-size: 100%; width:40px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"disclamer( '$state_db' );\"><img src=\"img/sport_disabled.png\"></button>";
        echo "<button class=\"pauseBtn_des\" title=\"в обеденное время и при отметке об уходе с рабочего места приостановка учета времени запрещена!\" style=\"font-size: 100%; width:40px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"disclamer( '$state_db' );\"><img src=\"img/pauseDisabled.png\"></button>";
        echo "</div>";
      }
      else {
        echo "<div class=\"right_button\">";
        echo "<button class=\"pauseBtn\" title=\"Удаленная работа\" style=\"font-size: 100%; width:40px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"remote_work();\"><img src=\"img/remoteWorkIcon2.png\" style=\"width: 15px; height: 14px;\"></button>";
        echo "<button class=\"pauseBtn\" title=\"Посещение тренажерного зала\" style=\"font-size: 100%; width:40px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"set_sport_pause();\"><img src=\"img/sport.png\"></button>";
        echo "<button class=\"pauseBtn\" title=\"Приостановка учета времени\" style=\"font-size: 100%; width:40px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"set_pause_header();\"><img src=\"img/pause.png\"></button>";
        echo "</div>";
      }
    echo "</div>";
  echo "</div>";

  $timeManagement .= "<table border=0><tr>";

  $timeRestributionWholeWidth = $btnWidth;
  $timeRestributionDescWidth = 440;
  $timeRestributionValWidth = $timeRestributionWholeWidth - $timeRestributionDescWidth;

  $timeRestribution .= "<table width=$timeRestributionWholeWidth  bordercolor=\"#888888\"  border=1>";
  $timeRestributionStat = "<table width=$timeRestributionWholeWidth  bordercolor=\"#888888\"  border=1>";


  if ( $state == 0 ) {
    $timeManagement .= "<td class=\"nopadding_s\" align=\"center\">";
      $timeManagement .= "<font size=\"4\" color=\"#ff0000\" face=\"Arial\">";
        $timeManagement .= "<b><br>Сведения за текущий рабочий день уже внесены!</b>";
      $timeManagement .= "</font>";
    $timeManagement .= "</td>";

    $timeRestribution .= in_time_part( $in_dt, $changeIn, $isThereDelayVal, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestribution .= eat_start_part( $eat_start_dt, $changeEatStart, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestribution .= eat_stop_part( $eat_stop_dt, $changeEatStop, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestribution .= out_time_part( $out_dt, $changeOut, $timeRestributionDescWidth, $timeRestributionValWidth );


    $timeRestributionStat .= pure_work_day_duration_part( $resultPureDurationWOEatStr, $userDayNormSec, 1, $timeRestributionDescWidth, $timeRestributionValWidth, 'Продолжительность присутствия без учета обеда', 0, 0 );
    $timeRestributionStat .= add_time_work_day_duration_part( $addWorkDurationStr, $addTimeDuration !=0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= add_pause_work_day_duration_part( $pauseWorkDurationStr, $pauseTimeDuration !=0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= eat_duration_part( $eatDurationStr, $eatNorm, 1, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= delay_part( $delayStr, $delayVal > 0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= empty_line();
    $timeRestributionStat .= pure_work_day_duration_part( $resultPureDurationStr, $userDayNormSec, 1, $timeRestributionDescWidth, $timeRestributionValWidth, 'Итог:', 1, 1 );
  }
  if ( $state == 2 ) {
    $timeManagement .= "<td class=\"nopadding_s\" height=10></td></tr><tr>";
    $timeManagement .= "<td class=\"nopadding_s\" style=\"font-size: 100%; margin:0; padding:0; margin-left:0;\">";
    $timeManagement .= "<button style=\"font-size: 110%; width:".$btnWidth."px; height:".$btnHeight."px; background-color:#f8d888; border:1px solid #888888; cursor:pointer;\" onclick=\"reg_eat_start(); return false;\">Зарегистрировать время ухода на обед</button>";
    $timeManagement .= "</td>";

    $timeRestribution .= change_time( $userID );
    $timeRestribution .= in_time_part( $in_dt, $changeIn, $isThereDelayVal, $timeRestributionDescWidth, $timeRestributionValWidth );

    $timeRestributionStat .= pure_work_day_duration_part( $resultPureDurationWOEatStr, 0, 0, $timeRestributionDescWidth, $timeRestributionValWidth, 'Продолжительность присутствия без учета обеда', 0, 0 );
    $timeRestributionStat .= add_time_work_day_duration_part( $addWorkDurationStr, $addTimeDuration !=0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= add_pause_work_day_duration_part( $pauseWorkDurationStr, $pauseTimeDuration !=0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= delay_part( $delayStr, $delayVal > 0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= empty_line();
    $timeRestributionStat .= pure_work_day_duration_part( $resultPureDurationStr, 0, 0, $timeRestributionDescWidth, $timeRestributionValWidth, 'Итог:', 1, 1 );
  }
  if ( $state == 3 ) {
    $timeManagement .= "<td class=\"nopadding_s\" height=10></td></tr><tr>";
    $timeManagement .= "<td class=\"nopadding_s\" style=\"font-size: 100%; margin:0; padding:0; margin-left:0;\">";
      $timeManagement .= "<button style=\"font-size: 100%; width:".$btnWidth."px; height:".$btnHeight."px; background-color:#f8d888; border:1px solid #888888; cursor:pointer;\" onclick=\"reg_eat_stop();\">Зарегистрировать время прихода с обеда</button>";
    $timeManagement .= "</td>";

    $timeRestribution .= change_time( $userID );
    $timeRestribution .= in_time_part( $in_dt, $changeIn, $isThereDelayVal, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestribution .= eat_start_part( $eat_start_dt, $changeEatStart, $timeRestributionDescWidth, $timeRestributionValWidth );

    $timeRestributionStat .= pure_work_day_duration_part( $resultPureDurationWOEatStr, 0, 0, $timeRestributionDescWidth, $timeRestributionValWidth, 'Продолжительность присутствия без учета обеда', 0, 0 );
    $timeRestributionStat .= add_time_work_day_duration_part( $addWorkDurationStr, $addTimeDuration !=0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= add_pause_work_day_duration_part( $pauseWorkDurationStr, $pauseTimeDuration !=0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= eat_duration_part( $eatDurationStr, $eatNorm, 1, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= delay_part( $delayStr, $delayVal > 0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= empty_line();
    $timeRestributionStat .= pure_work_day_duration_part( $resultPureDurationStr, 0, 0, $timeRestributionDescWidth, $timeRestributionValWidth, 'Итог:', 1, 1 );
  }
  if ( $state == 4 ) {
    $timeManagement .= "<td class=\"nopadding_s\" height=10></td></tr><tr>";
    $timeManagement .= "<td class=\"nopadding_s\" style=\"font-size: 100%; margin:0; padding:0; margin-left:0;\">";
      $timeManagement .= "<button style=\"font-size: 100%; width:".$btnWidth."px; height:".$btnHeight."px; background-color:#f8d888; border:1px solid #888888; cursor:pointer;\" onclick=\"reg_out_work(); return false;\">Зарегистрировать время ухода с рабочего места</button>";
    $timeManagement .= "</td>";

    $timeRestribution .= change_time( $userID );
    $timeRestribution .= in_time_part( $in_dt, $changeIn, $isThereDelayVal, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestribution .= eat_start_part( $eat_start_dt, $changeEatStart, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestribution .= eat_stop_part( $eat_stop_dt, $changeEatStop, $timeRestributionDescWidth, $timeRestributionValWidth );

    $timeRestributionStat .= pure_work_day_duration_part( $resultPureDurationWOEatStr, 0, 0, $timeRestributionDescWidth, $timeRestributionValWidth, 'Продолжительность присутствия без учета обеда', 0, 0 );
    $timeRestributionStat .= add_time_work_day_duration_part( $addWorkDurationStr, $addTimeDuration !=0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= add_pause_work_day_duration_part( $pauseWorkDurationStr, $pauseTimeDuration !=0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= eat_duration_part( $eatDurationStr, $eatNorm, 1, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= delay_part( $delayStr, $delayVal > 0, $timeRestributionDescWidth, $timeRestributionValWidth );
    $timeRestributionStat .= empty_line();
    $timeRestributionStat .= pure_work_day_duration_part( $resultPureDurationStr, 0, 0, $timeRestributionDescWidth, $timeRestributionValWidth, 'Итог:', 1, 1 );
  }
  $timeManagement .= "</tr>";
  $timeManagement .= "</table>";

  $timeRestribution .= "</table>";

  echo "<table border=0><tr height = 12><td></td></tr></table>";
  echo $timeRestribution;
  echo "<table border=0><tr height = 12><td></td></tr></table>";
  echo $timeRestributionStat;
  echo $timeManagement;

  echo "</td></tr>";
  echo "</table>";
}
?>
