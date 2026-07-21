<?php
// session_start();

require_once __DIR__ . '/inc/errors.php';
require_once __DIR__ . '/inc/database.php';
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/output.php';
require_once __DIR__ . '/inc/accounting_errors.php';
require_once __DIR__ . '/inc/workday_period.php';
require_once __DIR__ . '/inc/time_format.php';
require_once __DIR__ . '/inc/calendar.php';
require_once __DIR__ . '/inc/employee_directory.php';
require_once __DIR__ . '/inc/work_calendar.php';
require_once __DIR__ . '/inc/work_duration.php';
require_once __DIR__ . '/inc/delay.php';
require_once __DIR__ . '/inc/date_range.php';
require_once __DIR__ . '/inc/time_journal_repository.php';
require_once __DIR__ . '/inc/ajax_response.php';

function get_current_datetime_in_timezone(){
  $valid = 0;

  $dateStr = "";
  $timeStr = "";
  $datetime = "";
  $timeZoneMinsSrc = "";
  $timeZoneStr = "";

  if( isset($_SESSION['ss_sessid']) ){
    $timeZoneMinsSrc = $_SESSION['ss_UserTimeZoneMins'];
    $timeZoneSign = "+";            
    if ( $timeZoneMinsSrc < 0 ){
      $timeZoneSign = "-";
    }
        
    $timeZoneHours = floor( abs( $timeZoneMinsSrc ) / 60 );
    $timeZoneMins = abs( $timeZoneMinsSrc ) - $timeZoneHours * 60;

    $timeZoneHoursStr = (string)$timeZoneHours;
    $timeZoneMinsStr = (string)$timeZoneMins;

    if ( $timeZoneHours < 10 ) $timeZoneHoursStr = "0".$timeZoneHoursStr;
    if ( $timeZoneMins < 10 ) $timeZoneMinsStr = "0".$timeZoneMinsStr;

    $timeZoneStr = "UTC".$timeZoneSign.$timeZoneHoursStr.":".$timeZoneMinsStr;

    $datetime = gmdate("Y-m-d H:i:s");

    $datetime = date("Y-m-d H:i:s", strtotime($datetime."+ $timeZoneHours hour + $timeZoneMins minute"));

    $dateStr = substr($datetime, 0, 10);    
    $timeStr = substr($datetime, 11, 8);    

    $valid = 1;
  }
  else{
    session_destroy();
    $dateStr = "";
    $timeStr = "";
  }
  return array($valid, $datetime, $dateStr, $timeStr, $timeZoneMinsSrc, $timeZoneStr);
}

function sync_time_registration_session_by_period($link, $userID, $startDTStr, $stopDTStr){
  include __DIR__ . "/php_tori/connect.php";

  $userID = (int)$userID;

  $oldStart = isset($_SESSION['ss_startDTStr']) ? $_SESSION['ss_startDTStr'] : "";
  $oldStop = isset($_SESSION['ss_stopDTStr']) ? $_SESSION['ss_stopDTStr'] : "";

  if ($oldStart != "" && $oldStop != "" && ($oldStart != $startDTStr || $oldStop != $stopDTStr)) {
    unset($_SESSION['time_registration_cache']);
    unset($_SESSION['time_registration_div']);
  }

  $_SESSION['ss_startDTStr'] = $startDTStr;
  $_SESSION['ss_stopDTStr'] = $stopDTStr;

$maxOpenShiftHours = 3;
$maxOpenShiftSeconds = $maxOpenShiftHours * 60 * 60;

$currentDateTimeResult = get_current_datetime_in_timezone();
$currentDateTime = $currentDateTimeResult[1];

$query = db_query($link, "
  SELECT ID, state
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
    return;
  }

  if (mysqli_num_rows($query) == 0) {
    $_SESSION['ss_state'] = 1;
    $_SESSION['ss_visiting_ID'] = 0;
    return;
  }

  $row = mysqli_fetch_array($query, MYSQLI_ASSOC);

  $_SESSION['ss_state'] = (int)$row["state"];
  $_SESSION['ss_visiting_ID'] = (int)$row["ID"];
}

function get_splited_current_date_time_in_timezone()
{
    $retarr = get_current_datetime_in_timezone();
  
    $datevalStr = date( "Y-m-d", strtotime( $retarr[1] ) );
    $timevalStr = date( "H:i:s", strtotime( $retarr[1] ) );

    $dateval = strtotime( $datevalStr );
    $timeval = strtotime( $timevalStr );

    return array($dateval, $timeval, $datevalStr, $timevalStr);
}

function get_current_datetime_in_timezone_str( $showDate, $showTimeZone )
{
    $retStr = "";

    $result = get_current_datetime_in_timezone();

    $valid = $result[0];

    $dateStr = $result[2];
    $timeStr = $result[3];
    $timezoneStr = $result[5];

    // $timeZoneMins = $timezone; // неизвестная переменная $timezone
    // $timeZoneHours = round( $timeZoneMins / 60 );
    // $timeZoneMins = $timeZoneMins - $timeZoneHours * 60;

    if ( $valid == 1 )
    {
        $retStr = $dateStr;

        if ( $showDate == 1 )
        {
            $retStr = $retStr." ".$timeStr;
        }

        if ( $showTimeZone == 1 )
        {
            $retStr = $retStr." (".$timezoneStr.")";
        }
    }
    
    return $retStr;
}

function timezone_min_to_str( $timeZoneMinSrc )
{
    $sign = "+";

    if ( $timeZoneMinSrc < 0 )
    {
      $sign = "-";
    }

    $timeZoneMinSrc = (int)$timeZoneMinSrc;

    $timeZoneHour = round($timeZoneMinSrc / 60);
    $timeZoneMin = $timeZoneMinSrc - $timeZoneHour * 60;

    $timeZoneHourStr = (string)$timeZoneHour;
    $timeZoneMinStr = (string)$timeZoneMin;

    if ( $timeZoneHour < 10 )
    {
      $timeZoneHourStr = "0".$timeZoneHourStr;
    }

    if ( $timeZoneMin < 10 )
    {
      $timeZoneMinStr = "0".$timeZoneMinStr;
    }

    $timeZoneRes = "UTC".$sign.$timeZoneHour.":".$timeZoneMinStr;

    return $timeZoneRes;
}

function split_data_and_time_by_nl_str( $indatetime )
{
    $retStr = "";

    $datePart = substr( $indatetime, 0, 10);
    $timePart = substr( $indatetime, 11, 8);

    $retStr = $datePart." ".$timePart;

    return $retStr;
}

function datetime_to_time_str( $indatetime )
{
    $retStr = "";

    $timePart = substr( $indatetime, 11, 8);

    $retStr = $timePart;

    return $retStr;
}

function save_last_location( $location ){
  $_SESSION['ss_last_location'] = $location;
}  

function move_to_last_location(){
  if ( isset( $_SESSION['ss_last_location'] ) ){
    $lastLoc = $_SESSION['ss_last_location'];

    if ( strcmp($lastLoc, "index.php") == 0 && ( $_SESSION['ss_id'] == 500 || $_SESSION['ss_id'] == 501 ) ){
      $lastLoc = "my_report.php";
    }

    $loc = $lastLoc;
    header("Location: $loc");
    exit(); 
  }
  else
  { 
    header("Location: index.php");
  }
}  

function auth()
{
  $sessionIsValid = isset($_SESSION['ss_id'], $_SESSION['ss_sessid'])
    && hash_equals((string) $_SESSION['ss_sessid'], session_id());

  if (!$sessionIsValid)
  {
    header('Location: auth.php');
    exit;
  }

  require_once __DIR__ . '/inc/access.php';
  require_csrf_for_unsafe_request(false);
}  

function journal_status_label($text, $class = "middleBold_r")
{
  return "<h5 class=\"" . html_escape($class) . "\">" . html_escape($text) . "</h5>";
}

function get_users_current_day_in_time_by_superuser( $SUID )
{
  $users = get_users_by_superusers_and_type( $SUID, 3 );
  $allowedUsers = array_fill_keys(array_map('intval', $users), true);
  $rets = array();
  $seenUsers = array();

  $currentDateTime = get_current_datetime_in_timezone()[1];
  $dateRange = datetimestr_to_day_start_stop_DT_ex_str($currentDateTime, '00:00:00');

  include __DIR__ . "/php_tori/connect.php";

  $query = db_query($link, "
    SELECT v.user_id, v.in_dt, v.adj
    FROM visiting v
    INNER JOIN employees e ON v.user_id = e.id
    WHERE v.in_dt >= ?
      AND v.in_dt <= ?
    ORDER BY e.SURNAME, v.in_dt DESC, v.ID DESC
  ", 'ss', array($dateRange[0], $dateRange[1]));

  if (!$query)
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return $rets;
  }

  while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC))
  {
    $regUserID = (int)$row["user_id"];

    if (!isset($allowedUsers[$regUserID]) || isset($seenUsers[$regUserID])) {
      continue;
    }

    $seenUsers[$regUserID] = true;

    $tempArray = array();
    $tempArray[0] = $regUserID;
    $tempArray[1] = datetime_to_time_str($row["in_dt"]);
    $tempArray[2] = (int)$row["adj"];
    $rets[] = $tempArray;
  }

  return $rets;
}


function get_penalties( $userDays, $userID )
{
  $maxDate = max_date( $userDays );
  $minDate = min_date( $userDays );

  $penalties = array();
  $idx = 1;
  include __DIR__ . "/php_tori/connect.php";

  $query = db_query($link, "SELECT date from Penalty where date >= ? and date <= ? and userID = ?", 'ssi', array($minDate, $maxDate, (int)$userID));
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {
      $penaltyDate = $row["date"];

      for ( $idx2 = 1; $idx2 <= count( $userDays ); $idx2 ++ )
      {
        if ( $userDays[$idx2] == $penaltyDate )  
        {
          $penalties[$idx] = $penaltyDate;
          $idx ++;
          break;
        } 
      }
    }
  }
  return $penalties;
}


function get_current_day_duration_sec( $userID, $defaultStartTime ){
  include __DIR__ . "/php_tori/connect.php";

  $currentDateTime = get_current_datetime_in_timezone()[1];
  $dateRange = datetimestr_to_day_start_stop_DT_ex_str($currentDateTime, '00:00:00');

  $query = db_query($link, "
    SELECT in_dt
    FROM visiting
    WHERE user_id = ?
      AND state != 0
      AND in_dt >= ?
      AND in_dt <= ?
    ORDER BY in_dt DESC, ID DESC
    LIMIT 1
  ", 'iss', array((int)$userID, $dateRange[0], $dateRange[1]));

  if (!$query) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return 0;
  }

  $row = mysqli_fetch_assoc($query);

  if (!$row) {
    return 0;
  }

  return get_defined_time_range_duration($row["in_dt"], $currentDateTime);
}


function is_there_add_time_by_alert( $Date, $userID ){
  include __DIR__ . "/php_tori/connect.php";

  $query = time_journal_query_add_time_by_alert($link, $userID, $Date);
  $merr=mysqli_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    $vn=mysqli_num_rows($query);
    if ( $vn == 1 ){
      return 1;
    }
  }
  return 0;
}

function get_stat_by_range( $startDate, $stopDate, $userID, $user_defaultStartTime, $user_allowedDelay ){
  include __DIR__ . "/php_tori/connect.php";

  $holidays = get_holidays();
  $workDays = get_work_day();
  $daysRange = get_days_range($startDate, $stopDate);
  $penaltyDates = get_penalties($daysRange, $userID);

  $add_time_work_dayduration = 0;
  $full_work_day_duration = 0;
  $eat_work_day_duration = 0;
  $pure_work_day_duration = 0;
  $delay_count = 0;
  $delay_duration = 0;

  $periodStart = $startDate . ' 00:00:00';
  $periodStop = date('Y-m-d 00:00:00', strtotime($stopDate . ' +1 day'));
  $query1 = time_journal_query_approved_add_time($link, $userID, $periodStart, $periodStop);

  if (!$query1) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else {
    while ($row1 = mysqli_fetch_array($query1, MYSQLI_ASSOC)) {
      $clippedRange = clip_datetime_range($row1['START_DT'], $row1['STOP_DT'], $periodStart, $periodStop);

      if ($clippedRange !== null) {
        $add_time_work_dayduration += $clippedRange['duration'];
      }
    }
  }

  $query2 = db_query($link, "
    SELECT in_dt, out_dt, eat_start_dt, eat_stop_dt
    FROM visiting
    WHERE in_dt >= ?
      AND in_dt < ?
      AND user_id = ?
      AND state = 0
      AND out_dt > in_dt
    ORDER BY in_dt
  ", 'ssi', array($periodStart, $periodStop, (int)$userID));

  if (!$query2) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else {
    while ($row2 = mysqli_fetch_array($query2, MYSQLI_ASSOC)) {
      $visitStat = get_completed_visit_statistics(
        $row2["in_dt"],
        $row2["out_dt"],
        $row2["eat_start_dt"],
        $row2["eat_stop_dt"],
        $user_defaultStartTime,
        $user_allowedDelay
      );

      if ($visitStat === null) {
        continue;
      }

      $date = $visitStat['date'];
      $takeIntoAccount = !isWeekEnd($date)
        ? !in_array($date, $holidays, true)
        : in_array($date, $workDays, true);

      if (
        $takeIntoAccount
        && $visitStat['delay_duration'] > 0
        && in_array($date, $penaltyDates, true)
      ) {
        $delay_count++;
        $delay_duration += $visitStat['delay_duration'];
      }

      $full_work_day_duration += $visitStat['full_duration'];
      $eat_work_day_duration += $visitStat['lunch_duration'];
      $pure_work_day_duration += $visitStat['pure_duration'];
    }
  }

  return array(
    1 => $full_work_day_duration,
    2 => $pure_work_day_duration,
    3 => $add_time_work_dayduration,
    4 => $eat_work_day_duration,
    5 => $delay_count,
    6 => $delay_duration,
  );
}


function is_there_additional_alerts( $userID ){
  $currentDate = date('Y-m-d');

  include __DIR__ . "/php_tori/connect.php";

  $query = db_query($link, "SELECT 1 FROM ALERTS where DATE = ? and USERID = ? and VIEWED = '0' LIMIT 1", 'si', array($currentDate, (int)$userID));

  $merr=mysqli_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    while ( $row1 = mysqli_fetch_array($query, MYSQLI_ASSOC) ){  
      return 1;
    }
  }
  return 0;
}

function represent_is_time_defined( $time, $crossDayPeriod ){
  $valid = 1;

  if ( $crossDayPeriod == 0 ){
    if ( $time == "0000-00-00 00:00:00" ){
      $time = "_____-__-__ ___:___:__";
      $valid = 0;
    }
  }
  else{
    $time = substr( $time, 11, 8 );   
    if ( $time == "00:00:00" ){
      $time = "__:__:__";
      $valid = 0;
    }
  }

  return array($time, $valid);
}

function get_range_by_times_pair( $firstTime, $secondTime, $currentDay, $workTime, $defaultInTime, $allowedDelay, $crossDayPeriod ){
  $currentDate = Date("Y-m-d");

  $result = "<h5 class=\"middleSmall\">";

  $styleClass = "middleSmall";

  if ( $currentDay == "0" ){
    $styleClass = "middleRedSmall";
  }
  else{
    $styleClass = "middleInvisible";
  }        

  $timeArray = represent_is_time_defined($firstTime, $crossDayPeriod);
  $firstTime = $timeArray[0];
  $validTime = $timeArray[1];

  if ( $validTime == 1 ) {
    $result = $result . "<h5 class=\"middleSmall\">". $firstTime. " - </h5>";  
  }
  else 
  {
    $result = $result . "<h5 class=\"middleSmallGrey\">". $firstTime. " - </h5>";  
  }

  $timeArray = represent_is_time_defined($secondTime, $crossDayPeriod);

  $secondTime = $timeArray[0];
  $validTime  = $timeArray[1];

  if ( $validTime == 1 )
  {
    $result = $result . " <h5 class=\"middleSmall\">".$secondTime. "</h5>";  
  }
  else
  {
    $result = $result . "  <h5 class=\"middleSmallGrey\"> ". $secondTime. "</h5>";  
  }

  return $result;  
}

function get_delay_info_by_user_and_day( $userID_, $currentDate, $defauiltInTime, $allowedDelay ){
  include __DIR__ . "/php_tori/connect.php";
  mysqli_set_charset($link, "utf8"); 

  $rets = Array();

  $query0 = time_journal_query_delays_for_day($link, $userID_, $currentDate);

  if (!$query0) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return $rets;
  }

  while ( $row0 = mysqli_fetch_assoc($query0) ){
    $ID = $row0["id"];

    $supervisorID = $row0["supervisorID"];
    $acceptorID = $row0["acceptorID"];
    $explaneDesk = strip_tags($row0["explaneDesk"]);
    $penaltyID = $row0["penaltyID"];
    $penaltyReply = $row0["penaltyReply"];
    $status = $row0["status"];
    
    $query1 = time_journal_query_first_visit_for_day($link, $userID_, $currentDate);

    if (!$query1) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      return $rets;
    }
 
    $in_time = 0;

    if ( $row1 = mysqli_fetch_assoc($query1) ){
      $in_time = $row1["in_dt"];
    }

    $delayArr = get_delay_value($in_time, $defauiltInTime, $allowedDelay);
    $isThereDelay = $delayArr[0];
    $delayVal = $delayArr[1];

    if ($isThereDelay != 1) {
      continue;
    }

    $tempRets = Array();

    $tempRets[0] = $ID;
    $tempRets[1] = $supervisorID;
    $tempRets[2] = 0;
    $tempRets[3] = $explaneDesk;
    $tempRets[4] = $penaltyID;
    $tempRets[5] = $penaltyReply;
    $tempRets[6] = $status;
    $tempRets[7] = $delayVal;
    $tempRets[8] = $acceptorID;
    $tempRets[9] = $in_time;

    $rets[] = $tempRets;
  }   
  return $rets;
}

function get_delay_info_by_user_and_day_range( $userID, $startDate, $stopDate, $defauiltInTime, $allowedDelay )
{
  include __DIR__ . "/php_tori/connect.php";  
  mysqli_set_charset($link, "utf8");

  $query0 = time_journal_query_delays_for_range($link, $userID, $startDate, $stopDate);
  $merr=mysqli_error($link);
  if ( !$query0 ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return array();
  }

  $retArray = Array();

  while ( $row0 = mysqli_fetch_array($query0, MYSQLI_ASSOC) )
  {  
    $ID = $row0["id"];
    $delayDate = $row0["date"];
    $supervisorID = $row0["supervisorID"];
    $agreed = 10;/*$row0["agreed"];*/
    $explaneDesk = strip_tags($row0["explaneDesk"]);
    $acceptorID = $row0["acceptorID"];
    $penaltyID = $row0["penaltyID"];
    $penaltyReply = $row0["penaltyReply"];
    $status = $row0["status"];
    $in_time = $row0['in_dt'];

    if ($in_time !== null && $in_time !== '0000-00-00 00:00:00')
    {
      $delayArr = get_delay_value($in_time, $defauiltInTime, $allowedDelay);
      $delayVal = $delayArr[1];
      unset( $rets );
      $rets = Array();   

      $rets[0] = $ID;
      $rets[1] = $supervisorID;
      $rets[2] = $agreed;
      $rets[3] = $explaneDesk;
      $rets[4] = $penaltyID;
      $rets[5] = $penaltyReply;
      $rets[6] = $status;
      $rets[7] = $delayVal;
      $rets[8] = $in_time;
      $rets[9] = $defauiltInTime;
      $rets[10] = $allowedDelay;
      $rets[11] = $delayDate;
      $rets[12] = $acceptorID;

      $retArray[] = $rets;
    }
  }
  return $retArray;
} 

function get_reasons()
{
  include __DIR__ . "/php_tori/connect.php";  
  mysqli_set_charset($link, "utf8"); 

  $query0 = time_journal_query_reasons($link);

  $merr=mysqli_error($link);
  if ( !$query0 ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return array();
  }                     

  $results = Array();
 
  while ( $row0 = mysqli_fetch_array($query0, MYSQLI_ASSOC) )
  {
    $result = Array();
      
    $result[0] = $row0["ID"];
    $result[1] = $row0["DESCRIPTION"];
    
    $results[] = $result;
  }

  return $results;
}

 
function get_add_work_info_by_user_and_day_ex( $userID, $startDTStr, $stopDTStr, $restrictDTRangeToCurrentDay )
{
  include __DIR__ . "/php_tori/connect.php";  
  mysqli_set_charset($link, "utf8"); 

  $query = time_journal_query_add_work_for_period($link, $userID, $startDTStr, $stopDTStr);

  $merr=mysqli_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return array();
  }                     

  $results = Array();
 
  while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) ){
    $result = Array();
      
    $START_DT_VAL = $row["START_DT"];
    $STOP_DT_VAL = $row["STOP_DT"];

    if ( $restrictDTRangeToCurrentDay == 1 ){
      $clippedRange = clip_datetime_range($START_DT_VAL, $STOP_DT_VAL, $startDTStr, $stopDTStr);

      if ($clippedRange === null) {
        continue;
      }

      $START_DT_VAL = $clippedRange['start'];
      $STOP_DT_VAL = $clippedRange['stop'];
    }

    $result8 = $row["ID"];  

    $result[0] = $START_DT_VAL;
    $result[1] = $STOP_DT_VAL;

    $result[2] = $row["REASON"];
    $result[3] = $row["DESCRIPTION"];
    $result[4] = $row["APPROVED"];
    $result[5] = $row["SUIR"];
    $result[6] = 0;
    $result[7] = $row["PAUSE_MODE"];
    $result[8] = $row["ID"];  
    $result[9] = $row["START_DT"];  
    $result[10]= $row["SUPERVISORDESC"];  
    $result[11] = $row["REASONDESCRIPTION"];
    $result[6] = get_defined_time_range_duration($result[0], $result[1]);
     
    $results[] = $result;
  }

  return $results;
}

function colored_result( $prefix, $realTime, $needTime, $inverse, $check, $isresult ){
  $resultStr = format_time_d_hhmmss_pure( $realTime );

  if ( $isresult == 1 ){
    $colorClass = "bigbigbig";
  }
  else{
    $colorClass = "middle";
  }

  $resAdd1 = "(";
  $resAdd2 = ")";

  if( $isresult ) {
    $resAdd1 = "";
    $resAdd2 = "";
  }

  $result = "<h5 class=\"$colorClass\">$prefix$resAdd1$resultStr$resAdd2"; 


  if ( $check == 1 ){
    if( $inverse == 1 ){
      if ( $realTime > $needTime ){
        $result = "<h5 class=\"$colorClass"."Red\">$prefix$resAdd1$resultStr$resAdd2";
      }
    }
    else{
      if ( $realTime < $needTime ){
        $result = "<h5 class=\"$colorClass"."Red\">$prefix$resAdd1$resultStr$resAdd2";
      }
    }
  }
  return $result;
}

function colored_result_partial( $prefix, $realTime, $needTime, $inverse, $check, $isresult ){
  $resultStr = format_time_d_hhmmss_pure_partial( $realTime );

  if ( $isresult == 1 ){
    $colorClass = "bigbigbig";
  }
  else
  {
    $colorClass = "middle";
  }

  $resAdd1 = "(";
  $resAdd2 = ")";

  if( $isresult ) 
  {
    $resAdd1 = "";
    $resAdd2 = "";
  }

  $result = "<h5 class=\"$colorClass\">$prefix$resAdd1$resultStr$resAdd2"; 

  if ( $check == 1 )
  {
    if( $inverse == 1 )
    {
      if ( $realTime > $needTime )
      {
        $result = "<h5 class=\"$colorClass"."Red\">$prefix$resAdd1$resultStr$resAdd2";
      }
    }
    else
    {
      if ( $realTime < $needTime )
      {
        $result = "<h5 class=\"$colorClass"."Red\">$prefix$resAdd1$resultStr$resAdd2";
      }
    }
  }
  return $result;
}

function is_there_day_change( $in_dt, $eat_start_dt, $eat_stop_dt, $out_dt, $current_dt, $state )
{
  $isThereChange = 0;

  $changeIn = 0;
  $changeEatStart = 0;
  $changeEatStop = 0;
  $changeOut = 0;

  $in_d = strtotime(date("Y-m-d", strtotime($in_dt)));
  $eat_start_d = strtotime(date("Y-m-d", strtotime($eat_start_dt)));
  $eat_stop_d = strtotime(date("Y-m-d", strtotime($eat_stop_dt)));
  $out_d = strtotime(date("Y-m-d", strtotime($out_dt)));

  $current_d = strtotime(date("Y-m-d", strtotime($current_dt)));

  if ( $state == 2 )
  {
    if ( $in_d != $current_d ){ $isThereChange = 1; }
  }
  if ( $state == 3 )
  {
    if ( $in_d != $current_d ){ $isThereChange = 1; }
    if ( $eat_start_d != $current_d ){ $isThereChange = 1; }
  }
  if ( $state == 4 )
  {
    if ( $in_d != $current_d ){ $isThereChange = 1; }
    if ( $eat_start_d != $current_d ){ $isThereChange = 1; }
    if ( $eat_stop_d != $current_d ){ $isThereChange = 1; }
  }
  if ( $state == 0 )
  {
    if ( $in_d != $current_d ){ $isThereChange = 1; }
    if ( $eat_start_d != $current_d ){ $isThereChange = 1; }
    if ( $eat_stop_d != $current_d ){ $isThereChange = 1; }
    if ( $in_d != $current_d ){ $isThereChange = 1; }
  }

  if ( $isThereChange == 1 )
  {
    $changeIn = 1;
    $changeEatStart = 1;
    $changeEatStop = 1;
    $changeOut = 1;
  }
  return array( $changeIn, $changeEatStart, $changeEatStop, $changeOut, $isThereChange );
}

function is_there_day_change_betw( $in_dt, $eat_start_dt, $eat_stop_dt, $out_dt, $state )
{
  $isThereChange = 0;

  $changeIn = 0;
  $changeEatStart = 0;
  $changeEatStop = 0;
  $changeOut = 0;

  $in_d = strtotime(date("Y-m-d", strtotime($in_dt)));
  $eat_start_d = strtotime(date("Y-m-d", strtotime($eat_start_dt)));
  $eat_stop_d = strtotime(date("Y-m-d", strtotime($eat_stop_dt)));
  $out_d = strtotime(date("Y-m-d", strtotime($out_dt)));

  if ( $state == 3 )
  {
    if ( $in_d != $eat_start_d ){ $isThereChange = 1; }
  }
  if ( $state == 4 )
  {
    if ( $in_d != $eat_start_d || $in_d != $eat_stop_d ){ $isThereChange = 1; }
  }
  if ( $state == 0 )
  {
    if ( $in_d != $eat_start_d || $in_d != $eat_stop_d || $in_d != $out_d){ $isThereChange = 1; }
  }

  if ( $isThereChange == 1 )
  {
    $changeIn = 1;
    $changeEatStart = 1;
    $changeEatStop = 1;
    $changeOut = 1;
  }
  return array( $changeIn, $changeEatStart, $changeEatStop, $changeOut, $isThereChange );
}

function get_cell_content_by_stat( $stats, $index, $cellWidth, $userId, $defaultStartTimeStr, $user_allowedDelay ){
  $delayCheckEnabled = 1;

  if (
    !isset($defaultStartTimeStr) ||
    $defaultStartTimeStr == "" ||
    $defaultStartTimeStr == "NDF" ||
    strtotime($defaultStartTimeStr) === false
  ) {
    $delayCheckEnabled = 0;
    $defaultStartTimeStr = "NDF";
    $user_allowedDelay = 0;
  }

  if (!isset($user_allowedDelay) || $user_allowedDelay == "" || !is_numeric($user_allowedDelay)) {
    $user_allowedDelay = 0;
  }

  $user_allowedDelay = (int)$user_allowedDelay;

  // $dayTypes = get_workdays_holidays_bay_range( $startDate, $stopDate );
  $currentDateArr = get_current_datetime_in_timezone();
  $currentDate = $currentDateArr[2];

  $days_dates_set = $stats[0][$index];
  $days_work_start = $stats[1][$index];
  $days_work_stop = $stats[2][$index];
  $days_add_info = $stats[3][$index];
  $days_eat_start = $stats[5][$index];
  $days_eat_stop = $stats[6][$index];
  $days_day_type = $stats[8][$index];

  $days_penalties = $stats[10][$index];

  $days_day_state = $stats[15][$index];
  $days_day_currday = $stats[16][$index];
  $days_day_delay_duration = $stats[17][$index];
  
  $days_remoteWorkState = $stats[18][$index];
  $days_timeZoneSec = $stats[19][$index];
  $days_dayTransitionTime = $stats[20][$index];

  $days_leave_event = "NDF";

  if (isset($stats[21][$index])) {
    $days_leave_event = $stats[21][$index];
  }

  $isStaffLeave = 0;

  if ($days_leave_event == "Отпуск" || $days_leave_event == "Больничный") {
    $isStaffLeave = 1;
  }

  $days_timeZoneStr = timezone_min_to_str( $days_timeZoneSec );

  $isCurrentDay = 0;
  $notCurrentDay = 1;
  if ( $currentDate == $days_dates_set )
  {
    $isCurrentDay = 1;
    $notCurrentDay = 0;
  }	

  $dayNorm = 8 * 60 * 60;
  $eatNorm = 1 * 60 * 60;

  $errorDur = 0;

  $changesArr = is_there_day_change_betw( $days_work_start, $days_eat_start, $days_eat_stop, $days_work_stop, $days_day_state );
  $durations = get_durations( $days_work_start, $days_work_stop, $days_eat_start, $days_eat_stop, $days_add_info, $days_day_state, $days_day_currday );
  $crossDayPeriod = 0;//$changesArr[4];

  if ( $currentDate == $days_dates_set )
  {
    $isCurrentDay = 1;
    $notCurrentDay = 0;
  }	

  $isWeekend = isWeekEnd( $days_dates_set );
  $isholiday = 0;
  if ( $days_day_type >= 100 AND $days_day_type < 200 )
  {
    $isholiday = 1;
  }

  $isworkForceday = 0;
  if ( $days_day_type >= 200 )
  {
    $isworkForceday = 1;
  }

  $commonChechState = 1;
  $commonEatChechState = 1;
  if ( $isCurrentDay == 1 )
  {
    $commonChechState = 0;
  }
  else
  {  
    if ( $isWeekend == 1 )
    {
       if ( $isworkForceday == 0 )
       {
         $commonChechState = 0;
         $commonEatChechState = 0;
       }    
    }
    else
    {
      if ( $isholiday == 1 )
      {
        $commonChechState = 0;
        $commonEatChechState = 0;
      }
    }
  }


  $workWOEat = $durations[0];

  $workWOEatStr = colored_result( "", $workWOEat, $dayNorm, 0, $commonChechState, 0 );

  $resultTime = $durations[3];
  $resultTimeStr = colored_result( "", $resultTime, $dayNorm, 0, $commonChechState, 0 );

  $lunchDuration = $durations[1];
  $lunchDurationStr = colored_result( "", $lunchDuration, $eatNorm, 1, $commonEatChechState, 0 );

  $addTimeDuration = $durations[2];
  $addTimeDurationStr = colored_result( "", $addTimeDuration, 0, 0, 0, 0 );

  $pauseTimeDuration = $durations[5];
  $pauseTimeDurationStr = colored_result( "", $pauseTimeDuration, 0, 0, 0, 0 );

  $penaltyDuration = $days_day_delay_duration;
  $penaltyDurationStr = "";
 
  if ( $days_penalties == 1 )
  { 
    $penaltyDurationStr = format_time_d_hhmmss_pure( $penaltyDuration );
  }

  $resultPureTime = $durations[3];

  $needCheck = $notCurrentDay;

  if ( $currentDate == $days_dates_set AND is_time_defined( $days_work_stop ) == 1 )
  {
    $needCheck = 1;  
  }

  if ( $isWeekend OR $isholiday ){
    $needCheck = 0;  
  }

  if ($isStaffLeave == 1) {
    $needCheck = 0;
  }

  $resultPureTimeStr = colored_result( "", $resultTime, $dayNorm, 0, $needCheck, 1 );  
  $resultPureTimePartStr = colored_result_partial( "", $resultTime, $dayNorm, 0, $needCheck, 1 );  


  $dayColor = "#DDFFDD";
  $timeSpendImg = "img/workTimeGood.png";
  $lunchImg = "img/lunchTimeGood.png";
  $addTimeImg = "img/AddworkTimeGood.png";
  $pauseTimeImg = "img/PauseTimeGood.png";
  $addTimeListImg = "img/AddworkTimeListGood.png";
  $penaltyImg = "img/PenaltyGood.png";
  $remoteWorkImg = "img/remoteWorkGood.png";
  $cellaligment = "left";

  if ( $dayNorm > $resultTime )
  {
    $dayColor = "#FFDDDD";
    $timeSpendImg = "img/workTimeBad.png";
    $lunchImg = "img/lunchTimeBad.png";
    $addTimeImg = "img/AddworkTimeBad.png";
    $pauseTimeImg = "img/PauseTimeBad.png";
    $addTimeListImg = "img/AddworkTimeListBad.png";
    $penaltyImg = "img/PenaltyBad.png";
    $remoteWorkImg = "img/remoteWorkBad.png";
  }

  if ( $currentDate == $days_dates_set OR $isWeekend OR $isholiday )
  {
    $dayColor = "#ddeeff";
    $timeSpendImg = "img/workTimeCur.png";
    $lunchImg = "img/lunchTimeCur.png";
    $addTimeImg = "img/AddworkTimeCur.png";
    $pauseTimeImg = "img/PauseTimeCur.png";
    $addTimeListImg = "img/AddworkTimeListCur.png";
    $penaltyImg = "img/PenaltyCur.png";
    $remoteWorkImg = "img/remoteWorkCur.png";
  }

  if ( $isWeekend OR $isholiday ){
    $dayColor = "#C1CDC4";
    $timeSpendImg = "img/workTimeGood.png";
    $lunchImg = "img/lunchTimeGood.png";
    $addTimeImg = "img/AddworkTimeGood.png";
    $pauseTimeImg = "img/PauseTimeGood.png";
    $addTimeListImg = "img/AddworkTimeListGood.png";
    $penaltyImg = "img/PenaltyGood.png";
    $remoteWorkImg = "img/remoteWorkGood.png";
    $cellaligment = "left";
  }

  if ($isStaffLeave == 1){
    $dayColor = "#C1CDC4";
    $timeSpendImg = "img/workTimeGood.png";
    $lunchImg = "img/lunchTimeGood.png";
    $addTimeImg = "img/AddworkTimeGood.png";
    $pauseTimeImg = "img/PauseTimeGood.png";
    $addTimeListImg = "img/AddworkTimeListGood.png";
    $penaltyImg = "img/PenaltyGood.png";
    $remoteWorkImg = "img/remoteWorkGood.png";
    $cellaligment = "left";
  }

  
  $workDayRange = "";
  {
    $workDayRange = get_range_by_times_pair( $days_work_start, $days_work_stop, $isCurrentDay, $commonChechState, $defaultStartTimeStr, $user_allowedDelay, $crossDayPeriod );
  }

  $eatRange = "";
  {
    $eatRange = get_range_by_times_pair( $days_eat_start, $days_eat_stop, $isCurrentDay, 0, $defaultStartTimeStr, $user_allowedDelay, $crossDayPeriod );
  }

  $valignMode = "bottom";

  $isThereData = 1;


  $noDataStr = "Нет сведений!";
  $noDataStyle = "middleBoldRed";

  if ( $isWeekend AND ! $isholiday ){
    $noDataStr = "Выходной день";
    $noDataStyle = "middleBoldGreen";
  }
  else if ( $isholiday ){
    $noDataStr = "Праздничный день!";
    $noDataStyle = "middleBoldGreen";
  }
  else if ($isStaffLeave == 1){
    $noDataStr = $days_leave_event;
    $noDataStyle = "middleBoldGreen";
  }

  $prefix = "";

  if ( $days_dates_set == $currentDate )
  {
    if ( is_time_defined( $days_work_start ) == 0 
      && is_time_defined( $days_work_stop ) == 0 
      && is_time_defined( $days_eat_start ) == 0 
      && is_time_defined( $days_eat_stop ) == 0 
      && $addTimeDuration == 0 
      && $pauseTimeDuration == 0 
      && $penaltyDuration == 0 )
    {
      $prefix = "<h5 class=\"" . $noDataStyle . "\">" . $noDataStr;
      $isThereData = 0;
    } 
    else
    {
      $prefix = "<h5 class=\"middleBold\">Текущий день";
    }
  }
  else
  {
    if ( is_time_defined( $days_work_start ) == 0 
      && is_time_defined( $days_work_stop ) == 0 
      && is_time_defined( $days_eat_start ) == 0 
      && is_time_defined( $days_eat_stop ) == 0 
      && $addTimeDuration == 0 
      && $pauseTimeDuration == 0 
      && $penaltyDuration == 0 )
    {
      $prefix = "<h5 class=\"" . $noDataStyle . "\">" . $noDataStr;
      $isThereData = 0;
    }
  }

  $uidWork = 'u' . $userId . '-' . $days_dates_set . '-work';
  $uidLunch= 'u' . $userId . '-' . $days_dates_set . '-lunch';

  $outTimeEmpty = "";
  
  $workPureContent = "<h5 class=\"bigbig\">$resultPureTimeStr ($resultPureTimePartStr)</h5>";
  
  if ( $currentDate != $days_dates_set AND $errorDur == 1 )
  {
    $prefix = "<h5 class=\"bigmiddleRed\">Недостаточно сведений!";
  }

  if ( $isThereData == 1 )
  {
    $tableContent =   "<div class = \"right_table\">";
    $tableContent .=     "<div class = \"current_day\">"; 
     
    $tableContent .=       "<div class = \"report_no_padding_rep\">";
    $tableContent .=         $prefix;
    $tableContent .=       "</div>"; 
    if ($prefix == "<h5 class=\"middleBold\">Текущий день"){
      $tableContent .=       "<div class = \"report_no_padding_rep\">";
      $tableContent .=         "<h5 class=\"middleSmall\">$days_timeZoneStr</h5>";
      $tableContent .=       "</div>";
    }
    $tableContent .=      "</div>"; 

  
    $tableContent .=   "<div class = \"special_time_rep\">"; 
    $tableContent .=       "<div class = \"work_time_rep\">";
    $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"left\" width = 10px>";
    $tableContent .=             "<img title=\"рабочее время\" src=\"$timeSpendImg\"/>";
    $tableContent .=           "</div>"; 
      $tableContent .=          "<div class = \"report_no_padding_rep inf\" data-tooltip=\"$uidWork\" valign = \"top\" align = \"left\" width = 50px>";
      $tableContent .=            $workWOEatStr;
      $tableContent .=          "</div>";
    $tableContent .=        "</div>";
    $tableContent .=        "<div class=\"divs_layer\">";
    $tableContent .=          "<div class = \"report_no_padding_rep time\" data-tooltip-target=\"$uidWork\" align = \"center\" width = 230px>";
    $tableContent .=            $workDayRange;
    $tableContent .=          "</div>"; 
    $tableContent .=        "</div>";
    $showLegacyRemoteWorkStateInReport = 0;

    if ( $showLegacyRemoteWorkStateInReport == 1 && $days_remoteWorkState != 0 && $days_remoteWorkState != "NDF" ){
      $tableContent .=          "<div class = \"remote_work_time_rep\">";
      $tableContent .=              "<div class = \"report_no_padding_rep\" width = 15px>";
      $tableContent .=                  "<img title=\"удаленный режим работы\" src=\"$remoteWorkImg\"/>";
      $tableContent .=              "</div>";
      $tableContent .=              "<div class = \"report_no_padding_rep\" width = 45px>";
      $tableContent .=                  "<h5 class=\"middle\">(".$days_dayTransitionTime.")</h5>";
      $tableContent .=              "</div>";
      $tableContent .=           "</div>";
    }
    $tableContent .=   "</div>"; 
 
    $tableContent .=   "<div class = \"time_rep\">"; 
    $tableContent .=       "<div class = \"work_time_rep\">";
    $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 10px>";
    $tableContent .=             "<img title=\"обеденное время\" src=\"$lunchImg\"/>";
    $tableContent .=           "</div>"; 

      $tableContent .=           "<div class = \"report_no_padding_rep inf\" data-tooltip=\"$uidLunch\" align = \"left\" width = 50px>";
      $tableContent .=             $lunchDurationStr;
      $tableContent .=           "</div>"; 

    $tableContent .=      "</div>"; 
    $tableContent .=      "<div class=\"divs_layer\">";
    $tableContent .=        "<div class = \"report_no_padding_rep time\" data-tooltip-target=\"$uidLunch\" align = \"center\" width = 230px>";
    $tableContent .=          $eatRange;
    $tableContent .=        "</div>";
    $tableContent .=      "</div>";
    $tableContent .=   "</div>";

    $tableContent .=   "<div class = \"time_rep\">";

    if ( $addTimeDuration != 0 )
    {
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 15px>";
      $tableContent .=             "<img title=\"рабочее время вне офиса\" src=\"$addTimeImg\"/>";
      $tableContent .=           "</div>"; 
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"left\" width = 40px>";
      $tableContent .=             $addTimeDurationStr;
      $tableContent .=           "</div>";  
    }
    else
    {
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"left\" width = 40px style = \"display: none\">";
      $tableContent .=             "<img title=\"рабочее время вне офиса\" src=\"$addTimeImg\">";
      $tableContent .=             "<h5 class=\"middleGrey\">(__:__:__)</h5>";
      $tableContent .=           "</div>"; 
    }
    $tableContent .=   "</div>";

    if ( $pauseTimeDuration != 0 )
    { 
      $tableContent .=   "<div class = \"time_rep\">"; 
      $tableContent .=     "<div class = \"report_no_padding_rep\" align = \"center\" width = 10px>";
      $tableContent .=        "<img title=\"продолжительность приостановки учета времени\" src=\"$pauseTimeImg\"/>";
      $tableContent .=     "</div>"; 
      $tableContent .=     "<div class = \"report_no_padding_rep\" align = \"left\" width = 100px>";
      $tableContent .=        $pauseTimeDurationStr;
      $tableContent .=     "</div>"; 
      $tableContent .=   "</div>"; 
    }
    else
    {
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 60px style = \"display: none\">";
      $tableContent .=             "<img title=\"продолжительность приостановки учета времени\" src=\"$pauseTimeImg\">";
      $tableContent .=             "<h5 class=\"middleGrey\">(__:__:__)</h5>";
      $tableContent .=           "</div>";  
    } 

    if ( $penaltyDuration != 0 )
    {
    $tableContent .=   "<div class = \"time_rep\">"; 
    $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 15px>";
    $tableContent .=             "<img title=\"штрафные санкции за опоздание по неуважительной причине\" src=\"$penaltyImg\"/>";
    $tableContent .=           "</div>"; 
    $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 60px>";
    $tableContent .=             $penaltyDurationStr;
    $tableContent .=           "</div>"; 
    $tableContent .=        "</div>";
    }
    else
    {
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 60px style = \"display: none\">";
      $tableContent .=             "<img title=\"штрафные санкции за опоздание по неуважительной причине\" src=\"$penaltyImg\" >";
      $tableContent .=             "<h5 class=\"middleGrey\">(__:__:__)</h5>";
      $tableContent .=           "</div>"; 
    }
  $tableContent .=   "</div>"; 


  $tableContent .=   "<div class = \"result_time\">"; 
  $tableContent .=       "<div class = \"time_rep\">"; 
  $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"left\">";
  $tableContent .=               "$workPureContent";
  $tableContent .=           "</div>"; 
  $tableContent .=       "</div>"; 
  $tableContent .=  "</div>"; 
 

  $tableContent .= "</div>"; 
  }
  else
  {
    $tableContent  = "<table>";
    $tableContent .=   "<tr height = 95>";
    $tableContent .=     "<td width = 154px class = \"report_no_padding_rep\" align = center valign = middle>";
    $tableContent .=       $prefix;
    $tableContent .=     "</td>";
    $tableContent .=   "</tr>";
    $tableContent .= "</table>";
  }

  $unformattedContent1 = $prefix;
  // $unformattedContent2 = $workContent.$eatContent.$addTimeContent.$pauseTimeContent.$PenaltyContent.$workPureContent;
  
   if ($prefix == "<h5 class=\"middleBold\">Текущий день"){
   $content  = "<td class=\"report_no_padding\" bgcolor=\"$dayColor\" bordercolor=\"#888888\" valign=\"$valignMode\" align=\"$cellaligment\" width = $cellWidth>";
   $content .=   "<div class=\"report_body_head_day\" id=\"report_body_head_day\">"; 
     //$content .=     "$unformattedContent1$unformattedContent2";
   $content .=     "$tableContent";
   $content .=   "<div>"; 
   $content .= "</td>";
   }
   else {
    $content  = "<td class=\"report_no_padding\" bgcolor=\"$dayColor\" bordercolor=\"#888888\" valign=\"top\" align=\"$cellaligment\" width = $cellWidth>";
    $content .=   "<div class=\"report_body_head_day_first\" id=\"report_body_head_day_first\">"; 
    $content .=     "$tableContent";
    $content .=   "<div>"; 
    $content .= "</td>";
   }
    

  return $content;
}

function redmine_represent( $timeIn )
{

$timeInSrc = $timeIn;
 
  $hours = floor($timeIn / 3600);
  $timeIn = $timeIn - $hours * 3600;

  $minutes = round( $timeIn / 3600, 3 );

  $hoursStr = (string)$hours;

  $minutesStr = (string)$minutes;

  $minutesStrLen = strlen( $minutesStr );
  
  $minutesStr = substr( $minutesStr, 2, 2 );

  $result = $hoursStr.".".$minutesStr;

  return $result;
}


function get_results_cell_content_by_stat( $stats, $index, $cellWidth, $userID, $defaultStartTimeStr, $user_allowedDelay, $resType, &$typeShowed, &$headContent )
{
  $days_dates_set = $stats[0][$index];
  // echo $stats[0][0];

  $days_dates_results = $stats[13];


  $new_days_dates_set = DayIncDN( $days_dates_set, 1 );  

  $contentDT = "";
  $content = "";

  foreach( $days_dates_results as $results )
  {
    if ( $results[1] == $new_days_dates_set AND $results[5] == $resType )
    {   
      if ( $typeShowed == 0 )
      {
        $typeShowed = 1;

        if ( $resType == 1 OR $resType == 2 )
        {
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";  
          $contentDT .=   "<div class=\"report_head_left_date_rep_period\" id=\"report_head_left_date_rep_period\">"; 
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за период:<br></h5>";
          $contentDT .=     "<h6 class=\"mism1\"><br>$results[0]<br>-<br>$results[1]</h6>";
          $contentDT .=   "</div>"; 
          $contentDT .= "</td>"; 
          $headContent = $contentDT;
        }
        
        else if ( $resType == 3 )
        {
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
          $contentDT .=   "<div class=\"report_head_left_date_rep_week\" id=\"report_head_left_date_cert\">"; 
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за<br>неделю";
          $contentDT .=   "</div>"; 
          $contentDT .= "</td>"; 
          $headContent = $contentDT;
        }
        else if ( $resType == 4 ) {
          $monthName = GetMonthNameByDate( $days_dates_set );
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
          $contentDT .=   "<div class=\"report_head_left_date_rep_month\" id=\"report_head_left_date_cert\">";
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за<br>месяц:<br><h6 class=\"mism1\">".$monthName;
          $contentDT .=   "</div>"; 
          $contentDT .= "</td>"; 
          $headContent = $contentDT;
        }
        else if ( $resType == 5 )
        {
          $QuarterNum = GetQuarterRomNumByDate( $days_dates_set );
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
          $contentDT .=   "<div class=\"report_head_left_date_rep_quarter\" id=\"report_head_left_date_cert\">"; 
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за<br><h6 class=\"mism1\">$QuarterNum<h5 class=\"smallBlack\">квартал";
          $contentDT .=   "</div>"; 
          $contentDT .= "</td>"; 
          $headContent = $contentDT;
        }
        else if ( $resType == 6 )
        {
          $YearNum = GetCurrentYearD( $days_dates_set );
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
          $contentDT .=   "<div class=\"report_head_left_date_rep_year\" id=\"report_head_left_date_cert\">"; 
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за<br><h6 class=\"mism1\">$YearNum<h5 class=\"smallBlack\">год";
          $contentDT .=   "</div>"; 
          $contentDT .= "</td>"; 
          $headContent = $contentDT;
        }
      }

      if ( $results[2] < $results[6]  ) 
      {
	$resultColor = "#FFDDDD";
        $timeSpendImg = "img/workTimeBad.png";
        $lunchImg = "img/lunchTimeBad.png";
        $addTimeImg = "img/AddworkTimeBad.png";
        $pauseTimeImg = "img/PauseTimeBad.png";
        $addTimeListImg = "img/AddworkTimeListBad.png";
        $penaltyImg = "img/PenaltyBad.png";
        $overloadImg = "img/OverloadgBad.png";
        $overloadTitle = "недоработка до нормы за указанный интервал времени";
        $normImg = "img/NormBad.png";
        $overloadAbsolute = $results[6] - $results[2];
      }
      else
      {
	$resultColor = "#DDFFDD";
        $timeSpendImg = "img/workTimeGood.png";
        $lunchImg = "img/lunchTimeGood.png";
        $addTimeImg = "img/AddworkTimeGood.png";
        $pauseTimeImg = "img/PauseTimeGood.png";
        $addTimeListImg = "img/AddworkTimeListGood.png";
        $penaltyImg = "img/PenaltyGood.png";
        $overloadImg = "img/OverloadGood.png";
        $overloadTitle = "переработка сверх нормы за указанный интервал времени";
        $normImg = "img/NormGood.png";
        $overloadAbsolute = $results[2] - $results[6];
      }

      $content .= "<td class=\"report_no_padding\" bgcolor=\"$resultColor\" bordercolor=\"#888888\" valign=\"top\" align=\"left\">";
      $content .=   "<div class=\"report_body_head_summary\" id=\"report_body_head_summary\">"; 

      $content .=            "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $content .=                  "<img title=\"фактическая наработка за указанный интервал времени без учета обеда\" src=\"$timeSpendImg\"/>";
      $content .=                "</div>";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $Val2 = (int)($results[2]);              
      $Val4 = (int)($results[4]); 
      $Val3 = (int)($results[3]); 
      $Val9 = (int)($results[9]); 

$Val = $Val2 + $Val4 - $Val3 + $Val9;              
      $content .=                  format_time_d_hhmmss_pure_styled( $Val );
             
      $content .=                "</div>";

      $content .=          "</div>";

      $content .=            "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $content .=                  "<img title=\"обеденное время за указанный интервал времени\" src=\"$lunchImg\"/>";
      $content .=                "</div>";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $Val = (int)($results[4]);              
      $content .=                  format_time_d_hhmmss_pure_styled( $Val );
             
      $content .=                "</div>";
      $content .=            "</div>";

      $content .=            "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 5px>";
      $content .=                  "<img title=\"рабочее время вне офиса за указанный интервал времени\" src=\"$addTimeImg\"/>";
      $content .=                "</div>";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $Val = (int)($results[3]);              
      $content .=                  format_time_d_hhmmss_pure_styled( $Val );
             
      $content .=                "</div>";
      $content .=            "</div>";

      $content .=            "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $content .=                  "<img title=\"приостановки учета времени за указанный интервал времени\" src=\"$pauseTimeImg\"/>";
      $content .=                "</div>";
      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 8px>";
      $Val = (int)($results[9]);              
      $content .=                  format_time_d_hhmmss_pure_styled( $Val );
             
      $content .=                "</div>";
      $content .=            "</div>";

      $content .=        "<div class = \"time_rep\">";

      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 3px>";
      $content .=                  "<img title=\"штрафные санкции за опоздание по неуважительной причине за указанный интервал времени\" src=\"$penaltyImg\"/>";
      $content .=                "</div>";

      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 152px>";
      $Val = (int)($results[7]);              
      $ValC = (int)($results[8]);              
      $ValP = $ValC * 1000;              
      $content .=                 format_time_d_hhmmss_pure_styled( $Val );
      if ($ValC > 0)
      {
        $content .=                  "<h3 class=\"small1\"> [".(string)$ValC."x1000 = ".$ValP."р]</h3>";
      }       
      $content .=                "</div>";
      $content .=        "</div>";

      $content .=        "<div class = \"result\">";
      $content .=             "<h5 class=\"middleSmall\">Итог:</h5>";

      $content .=        "</div>";

      $content .=        "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 10px>";
      $ValNormBeforeLeaves = isset($results[10]) ? (int)($results[10]) : (int)($results[6]);
      $ValLeaveHours = isset($results[11]) ? (int)($results[11]) : 0;
      $ValNormAfterLeaves = (int)($results[6]);
      $ValFact = (int)($results[2]);

      $ValRedmine = redmine_represent($ValFact);

      $content .= "<h5 class=\"middle\" title=\"Норма часов за период с учетом выходных, праздников и предпраздничных дней, но без вычета отпуска и больничного\"> "
        . format_time_d_hhmmss_pure_HH($ValNormBeforeLeaves)
        . " - Норма (ч.)</h5></br>";

      if ($ValLeaveHours > 0) {
        $content .= "<h5 class=\"middle\" title=\"Количество часов отпуска и больничного, вычтенное из нормы за выбранный период\"> "
          . format_time_d_hhmmss_pure_HH($ValLeaveHours)
          . " - Отсутствие (ч.)</h5></br>";
      }

      $content .= "<h5 class=\"middle\" title=\"Норма часов к отработке после вычета отпуска и больничного\"> "
        . format_time_d_hhmmss_pure_HH($ValNormAfterLeaves)
        . " - К отработке (ч.)</h5></br>";

      $content .= "<span title=\"Фактически отработанное время за выбранный период\">"
        . format_time_d_hhmmss_pure_styled($ValFact)
        . "(" . $ValRedmine . ")"
        . "</span><h5 class=\"middle\" title=\"Фактически отработанное время за выбранный период\"> - Факт </h5>";      $content .=        "</div>";

      $content .=      "</div>";

     $content .=   "</div>"; 
     $content .= "</td>";
    }   
  }

  return $content;
}

function getMaskedUID( $symcnt, $uid )
{
  $valStr = "";
  $uidStr = (string)$uid;
  $uidStrMaxLen = 3;
  $uidStrLen = strlen($uidStr);
  $addCnt = $uidStrMaxLen - $uidStrLen;
  
  for ($i = 0; $i < $addCnt; $i ++ )
  {
    $uidStr = "0".$uidStr;
  }       

  for ($i = 0; $i < $symcnt; $i ++ )
  {
    $val = rand(0, 9);
    $valStr = $valStr . (string)$val;
  }

  $valStr[10] = $uidStr[0];
  $valStr[11] = $uidStr[1];
  $valStr[12] = $uidStr[2];

  $msgHash = hash('crc32', $valStr );

  $msgHashPart1 = substr( $msgHash, 0, 2 );
  $msgHashPart2 = substr( $msgHash, 2, 2 );
  $msgHashPart3 = substr( $msgHash, 4, 2 );
  $msgHashPart4 = substr( $msgHash, 6, 2 );

  $retStrPart1 = substr( $valStr, 0, 6 );
  $retStrPart2 = substr( $valStr, 6, 9 );
  $retStrPart3 = substr( $valStr, 15, 7 );
  $retStrPart4 = substr( $valStr, 22, 10 );

  $valStrRes = $retStrPart4.$msgHashPart1.$retStrPart3.$msgHashPart2.$retStrPart2.$msgHashPart3.$retStrPart1.$msgHashPart4;

  $valStrRes = strtoupper( $valStrRes );

  return $valStrRes;
}

function extractUidFromMaskedUID( $maskedStr )
{
  $maskedStrIdPart4 = substr( $maskedStr, 0, 10 );
  $maskedStrIdPart3 = substr( $maskedStr, 12, 7 );
  $maskedStrIdPart2 = substr( $maskedStr, 21, 9 );
  $maskedStrIdPart1 = substr( $maskedStr, 32, 6 );

  $maskedStrHashPart1 = substr( $maskedStr, 10, 2 );
  $maskedStrHashPart2 = substr( $maskedStr, 19, 2 );
  $maskedStrHashPart3 = substr( $maskedStr, 30, 2 );
  $maskedStrHashPart4 = substr( $maskedStr, 38, 2 );

  $maskedIdStrCheck = $maskedStrIdPart1.$maskedStrIdPart2.$maskedStrIdPart3.$maskedStrIdPart4;

  $maskedHashStrCheck = $maskedStrHashPart1.$maskedStrHashPart2.$maskedStrHashPart3.$maskedStrHashPart4;

  $msgHash = hash('crc32', $maskedIdStrCheck );

  $msgHash = strtoupper( $msgHash );

  $uidVal = -1;
  $valid = 0;

  if ( strcasecmp($msgHash, $maskedHashStrCheck) == 0 ) 
  {
    $uidStr = substr($maskedIdStrCheck, 10, 3 );
    $uidVal = (int)$uidStr;
    $valid = 1;
  }

  return array( $valid, $uidVal );
}

function shift_dt_by_transition_time( $dateTime, $transTime, $shiftDir )
{
  $transTimeH = (int)date("H", strtotime($transTime));
  $transTimeM = (int)date("i", strtotime($transTime));
  $transTimeS = (int)date("s", strtotime($transTime));

  if ( $shiftDir == 1 )
  {
    $dateTime = date("Y-m-d H:i:s", strtotime( "+$transTimeH hour +$transTimeM minute +$transTimeS second", strtotime( $dateTime ) ) );
  }
  if ( $shiftDir == -1 )
  {
    $dateTime = date("Y-m-d H:i:s", strtotime( "-$transTimeH hour -$transTimeM minute -$transTimeS second", strtotime( $dateTime ) ) );
  }
    
  return $dateTime;
}

?>
