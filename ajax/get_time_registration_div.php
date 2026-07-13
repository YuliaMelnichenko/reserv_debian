<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
date_default_timezone_set("Asia/Novosibirsk");
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include __DIR__ . "/../php_tori/connect.php";

function change_time ($user) {
  include __DIR__ . "/../php_tori/connect.php";
  include_once __DIR__ . "/../funcs.php";

  mysqli_set_charset($link, "utf8");

  $currentTime = date("H:i:s");
  $currentDayNumber = GetWeekDayD(date("Y-m-d"));
  $daysBack = ($currentDayNumber == "1") ? 3 : 1;

  $content = "";

  $visitQuery = db_query(
    $link,
    "SELECT ID, out_dt, eat_start_dt, eat_stop_dt
     FROM visiting
     WHERE user_id = ?
       AND DATE(in_dt) = DATE(DATE_SUB(CURRENT_TIMESTAMP, INTERVAL $daysBack DAY))
     ORDER BY in_dt DESC, ID DESC
     LIMIT 1",
    'i',
    array((int)$user)
  );

  if (!$visitQuery || mysqli_num_rows($visitQuery) == 0) {
    return $content;
  }

  $row = mysqli_fetch_assoc($visitQuery);
  $visitID = (int)$row["ID"];
  $out_value = isset($row["out_dt"])
    ? $row["out_dt"]
    : "0000-00-00 00:00:00";

  $eat_start_value = isset($row["eat_start_dt"])
    ? $row["eat_start_dt"]
    : "0000-00-00 00:00:00";

  $eat_stop_value = isset($row["eat_stop_dt"])
    ? $row["eat_stop_dt"]
    : "0000-00-00 00:00:00";

  $hasOpenLunch = ($eat_start_value !== "0000-00-00 00:00:00" && $eat_stop_value == "0000-00-00 00:00:00");

  $content .= "<input type=\"hidden\" id=\"change_visit_id\" value=\"$visitID\">";

  if ( $hasOpenLunch ){
    $content .= "<tr>";
    $content .= change_eat_stop_time( $currentTime);
    $content .= "</tr>";
    $content .= "<tr>";
    $content .= change_out_time_disabled( $out_value, $currentTime );
    $content .= "</tr>";
  }
  else {
    $content .= "<tr>";
    $content .= change_out_time( $out_value, $currentTime );
    $content .= "</tr>";
  }

  return $content;
}

function change_out_time ( $out_value, $currentTime ) {
  $content = "";

  if ( $out_value == "0000-00-00 00:00:00" ) {
    if ( $currentTime >= "09:00:00" && $currentTime < "19:30:00" ) {
      $content .= "<td class=\"nopadding_s\">";
      $content .= "<h5 class=\"change_time\">Добавить время ухода?</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s time-change-action-cell time-value-good\">";
      $content .= "<button id=\"add_out_time\" class=\"time-change-button\" title=\"Добавить время\" onclick=\"enter_out_time();\"><img src=\"img/red.png\" alt=\"\"></button>";
      $content .= "</td>";
    }
  }
  return $content;
}

function change_out_time_disabled ( $out_value, $currentTime ) {
  $content = "";

  if ( $out_value == "0000-00-00 00:00:00" ) {
    if ( $currentTime >= "09:00:00" && $currentTime < "19:30:00" ) {
      $content .= "<td class=\"nopadding_s\">";
      $content .= "<h5 class=\"change_time\">Добавить время ухода?</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s time-change-action-cell time-value-disabled\">";
      $content .= "<button id=\"add_out_time_disabled\" class=\"time-change-button time-change-button-disabled\" disabled title=\"Сначала добавьте время прихода с обеда\"><img src=\"img/red.png\" alt=\"\"></button>";
      $content .= "</td>";
    }
  }

  return $content;
}

function change_eat_stop_time ( $currentTime ) {
  $content = "";

  if ( $currentTime >= "09:00:00" && $currentTime < "11:30:00" ) {
    $content .= "<td class=\"nopadding_s\">";
    $content .= "<h5 class=\"change_time\">Добавить время прихода с обеда?</h5>";
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s time-change-action-cell time-value-good\">";
    $content .= "<button id=\"add_stop_eat\" class=\"time-change-button\" title=\"Добавить время\" onclick=\"enter_stop_eat_time();\"><img src=\"img/din.png\" alt=\"\"></button>";
    $content .= "</td>";
  }
  return $content;
}

function in_time_part( $datetime, $crossDay, $isThereDelay, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $valueClass = "";
  $content = "";
  $content .= "<tr>";
    $content .= "<td class=\"nopadding_s time-description-cell\">";
      $content .= "<h5 class=\"big\">Время прихода на рабочее место</h5>";
    $content .= "</td>";

    if ( $isThereDelay == 1 ) {
      $valueClass = 'time-value-warning';
    }
    if ( $isThereDelay == 2 ) {
      $valueClass = 'time-value-bad';
    }

    $content .= "<td class=\"nopadding_s time-value-cell $valueClass\">";
    if ( $isThereDelay == 2 ) { 
      if ( $crossDay == 1 ) { 
        $datetime = split_data_and_time_by_nl_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      else {
        $datetime = datetime_to_time_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      $content .= " <button id=\"explBtn\" class=\"time-explanation-button\" title=\"Внести объяснение к опозданию\" onclick=\"add_expl();\"><img src=\"img/report_small.png\" alt=\"\"></button>";
    }
    else {
      if ( $crossDay == 1 ) { 
        $datetime = split_data_and_time_by_nl_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      else {
        $datetime = datetime_to_time_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
    }
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function eat_start_part( $datetime, $crossDay, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";
  $content .= "<tr>";
    $content .= "<td class=\"nopadding_s time-description-cell\">";
      $content .= "<h5 class=\"big\">Время ухода на обед</h5>";
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s time-value-cell\">";
    if ( $crossDay == 1 ) { 
      $datetime = split_data_and_time_by_nl_str( $datetime );
      $content .= "<h5 class=\"big\">".$datetime."</h5>";
    }
    else {
      $datetime = datetime_to_time_str( $datetime );
      $content .= "<h5 class=\"big\">".$datetime."</h5>";
    }
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function eat_stop_part( $datetime, $crossDay, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";
    $content .= "<tr>";
      $content .= "<td class=\"nopadding_s time-description-cell\">";
        $content .= "<h5 class=\"big\">Время прихода с обеда</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s time-value-cell\">";
      if ( $crossDay == 1 ) { 
        $datetime = split_data_and_time_by_nl_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      else {
        $datetime = datetime_to_time_str( $datetime );
        $content .= "<h5 class=\"big\">".$datetime."</h5>";
      }
      $content .= "</td>";
    $content .= "</tr>";
  return $content;
}

function out_time_part( $datetime, $crossDay, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";
  $content .= "<tr>";
    $content .= "<td class=\"nopadding_s time-description-cell\">";
      $content .= "<h5 class=\"big\">Время ухода с рабочего места</h5>";
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s time-value-cell time-value-good\">";
    if ( $crossDay == 1 ) { 
      $datetime = split_data_and_time_by_nl_str( $datetime );
      $content .= "<h5 class=\"big\">".$datetime."</h5>";
    }
    else {
      $datetime = datetime_to_time_str( $datetime );
      $content .= "<h5 class=\"big\">".$datetime."</h5>";
    }
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function empty_line() {
  $content = "";
  $content .= "<tr class=\"time-spacer-row\">";
  $content .=   "<td class=\"nopadding_s time-align-right\">";
  $content .=   "</td>";
  $content .= "</tr>";
  return $content;
}

function pure_work_day_duration_part( $time, $norm, $check, $timeRestributionDescWidth, $timeRestributionValWidth, $msg, $rightAlign, $showRMTime ) {
  $valueClass = "";
  $addonStr = "";

  if ( $showRMTime == 1 ) {
    $tms = time_to_second( $time ); 
    $formatedTime =redmine_represent( $tms );
    $addonStr = " (RM: ".$formatedTime.")";
  }

  if( $check == 1 ) {
    if ( strtotime( $time ) >= strtotime( $norm ) ) {
      $valueClass = "time-value-good";
    }
    else {
      $valueClass = "time-value-bad";
    }    
  }
  $content = "";
  $content .= "<tr>";
    if ( $rightAlign == 1 ) {
      $content .= "<td class=\"nopadding_s time-description-cell time-align-right\">";
      $content .= "<h5 class=\"biggreen1\">$msg</h5>";
    }
    else {
      $content .= "<td class=\"nopadding_s time-description-cell\">";
      $content .= "<h5 class=\"biggreen1\">$msg</h5>";
    }
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s time-value-cell $valueClass\" title=\"Зеленый: продолжительность не менее нормы. Красный: меньше нормы\">";
      $content .= "<h5 class=\"big\">".$time.$addonStr."</h5>";
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function add_time_work_day_duration_part( $time, $valid, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";
 
  if ( $valid ) {
    $content .= "<tr>";
      $content .= "<td class=\"nopadding_s time-description-cell\">";
        $content .= "<h5 class=\"biggreen1\">Продолжительность работы вне офиса</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s time-value-cell\">";
        $content .= "<h5 class=\"big\">".$time."</h5>";
      $content .= "</td>";
    $content .= "</tr>";
  } 
  return $content;
}

function add_pause_work_day_duration_part( $time, $valid, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";
 
  if ( $valid ) {
    $content .= "<tr>";
      $content .= "<td class=\"nopadding_s time-description-cell\">";
        $content .= "<h5 class=\"bigred\">Продолжительность приостановки учета времени</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s time-value-cell\">";
        $content .= "<h5 class=\"big\">".$time."</h5>";
      $content .= "</td>";
    $content .= "</tr>";
  } 
  return $content;
}

function eat_duration_part( $time, $norm, $check, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $valueClass = "";

  if( $check == 1 ) {
    if ( strtotime( $time ) <= strtotime( $norm ) ) {
      $valueClass = "time-value-good";
    }
    else {
      $valueClass = "time-value-bad";
    }    
  }

  $content = "";
  $content .= "<tr>";
    $content .= "<td class=\"nopadding_s time-description-cell\">";
      $content .= "<h5 class=\"biggreen1\">Продолжительность обеденного времени</h5>";
    $content .= "</td>";
    $content .= "<td class=\"nopadding_s time-value-cell $valueClass\" title=\"Зеленый: обед не более часа. Красный: свыше часа\">";
      $content .= "<h5 class=\"big\">".$time."</h5>";
    $content .= "</td>";
  $content .= "</tr>";
  return $content;
}

function delay_part( $time, $valid, $timeRestributionDescWidth, $timeRestributionValWidth ) {
  $content = "";
  if ( $valid ) {
    $content .= "<tr>";
      $content .= "<td class=\"nopadding_s time-description-cell\">";
        $content .= "<h5 class=\"biggreen1\">Длительность опоздания</h5>";
      $content .= "</td>";
      $content .= "<td class=\"nopadding_s time-value-cell\">";
        $content .= "<h5 class=\"big\">".$time."</h5>";
      $content .= "</td>";
    $content .= "</tr>";
  }
  return $content;
}

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
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
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

  echo "<table class=\"time-management-table\">";
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
      echo "<button id=\"reg_in_work_button\" class=\"time-primary-button\" onclick=\"reg_in_work();\">Зарегистрировать время прихода</button>";
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
      echo "<button class=\"time-primary-button time-primary-button-late\" onclick=\"reg_in_work_with_delay();\">Зарегистрировать приход с опозданием!</button>";
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

  echo "<table class=\"time-management-table\">";
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
      echo "<button id=\"time_back\" class=\"time-state-icon-button\" title=\"Возврат состояния регистрации времени до предыдущего\" onclick=\"rollback_state();\"><img src=\"img/rollbackState.png\" alt=\"\"></button>";
    echo "</div>";
    echo "<div class=\"nopadding_s time-state-actions\">";
      if ($state == 3 || $state == 0) {
        echo "<div class=\"right_button\">";
        echo "<button class=\"pauseBtn_des time-state-icon-button\" title=\"В этом состоянии изменения запрещены\" onclick=\"remote_work();\"><img class=\"time-state-small-icon\" src=\"img/remoteWorkIcon2.png\" alt=\"\"></button>";
        echo "<button class=\"pauseBtn_des time-state-icon-button\" title=\"В этом состоянии посещение спортзала запрещено\" onclick=\"disclamer($state_db);\"><img src=\"img/sport_disabled.png\" alt=\"\"></button>";
        echo "<button class=\"pauseBtn_des time-state-icon-button\" title=\"В этом состоянии приостановка запрещена\" onclick=\"disclamer($state_db);\"><img src=\"img/pauseDisabled.png\" alt=\"\"></button>";
        echo "</div>";
      }
      else {
        echo "<div class=\"right_button\">"; 
        echo "<button class=\"pauseBtn time-state-icon-button\" title=\"Удаленная работа\" onclick=\"remote_work();\"><img class=\"time-state-small-icon\" src=\"img/remoteWorkIcon2.png\" alt=\"\"></button>";
        echo "<button class=\"pauseBtn time-state-icon-button\" title=\"Посещение тренажерного зала\" onclick=\"set_sport_pause();\"><img src=\"img/sport.png\" alt=\"\"></button>";
        echo "<button class=\"pauseBtn time-state-icon-button\" title=\"Приостановка учета времени\" onclick=\"set_pause_header();\"><img src=\"img/pause.png\" alt=\"\"></button>";
        echo "</div>";
      }
    echo "</div>";
  echo "</div>";

  $timeManagement .= "<table class=\"time-management-table\"><tr>";
  
  $timeRestributionWholeWidth = $btnWidth;
  $timeRestributionDescWidth = 440;
  $timeRestributionValWidth = $timeRestributionWholeWidth - $timeRestributionDescWidth;
 
  $timeRestribution .= "<table class=\"time-registration-details\">";
  $timeRestributionStat = "<table class=\"time-registration-details\">";


  if ( $state == 0 ) {
    $timeManagement .= "<td class=\"nopadding_s time-complete-message\">";
      $timeManagement .= "<strong><br>Сведения за текущий рабочий день уже внесены!</strong>";
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
    $timeManagement .= "<td class=\"nopadding_s time-management-cell\">";
    $timeManagement .= "<button class=\"time-primary-button\" onclick=\"reg_eat_start(); return false;\">Зарегистрировать время ухода на обед</button>";
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
    $timeManagement .= "<td class=\"nopadding_s time-management-cell\">";
      $timeManagement .= "<button class=\"time-primary-button\" onclick=\"reg_eat_stop();\">Зарегистрировать время прихода с обеда</button>";
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
    $timeManagement .= "<td class=\"nopadding_s time-management-cell\">";
      $timeManagement .= "<button class=\"time-primary-button\" onclick=\"reg_out_work(); return false;\">Зарегистрировать время ухода с рабочего места</button>";
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
    
  echo "<div class=\"time-section-spacer\"></div>";
  echo $timeRestribution;
  echo "<div class=\"time-section-spacer\"></div>";
  echo $timeRestributionStat;
  echo $timeManagement;

  echo "</td></tr>";
  echo "</table>";
}
?>
