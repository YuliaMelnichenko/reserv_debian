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
require_once __DIR__ . '/inc/report_statistics.php';
require_once __DIR__ . '/inc/report_presentation.php';
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

  if (db_num_rows($query) == 0) {
    $_SESSION['ss_state'] = 1;
    $_SESSION['ss_visiting_ID'] = 0;
    return;
  }

  $row = db_fetch_one($query);

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
  require_once __DIR__ . '/inc/access.php';

  if (!access_session_is_valid())
  {
    header('Location: auth.php');
    exit;
  }

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

  while ($row = db_fetch_one($query))
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

function is_there_add_time_by_alert( $Date, $userID ){
  include __DIR__ . "/php_tori/connect.php";

  $query = time_journal_query_add_time_by_alert($link, $userID, $Date);
  $merr = db_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    $vn = db_num_rows($query);
    if ( $vn == 1 ){
      return 1;
    }
  }
  return 0;
}



function is_there_additional_alerts( $userID ){
  $currentDate = date('Y-m-d');

  include __DIR__ . "/php_tori/connect.php";

  $query = db_query($link, "SELECT 1 FROM ALERTS where DATE = ? and USERID = ? and VIEWED = '0' LIMIT 1", 'si', array($currentDate, (int)$userID));

  $merr = db_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    while ( $row1 = db_fetch_one($query) ){
      return 1;
    }
  }
  return 0;
}



function get_delay_info_by_user_and_day( $userID_, $currentDate, $defauiltInTime, $allowedDelay ){
  include __DIR__ . "/php_tori/connect.php";
  db_set_charset($link, "utf8");

  $rets = Array();

  $query0 = time_journal_query_delays_for_day($link, $userID_, $currentDate);

  if (!$query0) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return $rets;
  }

  while ( $row0 = db_fetch_one($query0) ){
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

    if ( $row1 = db_fetch_one($query1) ){
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
  db_set_charset($link, "utf8");

  $query0 = time_journal_query_delays_for_range($link, $userID, $startDate, $stopDate);
  $merr = db_error($link);
  if ( !$query0 ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return array();
  }

  $retArray = Array();

  while ( $row0 = db_fetch_one($query0) )
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
  db_set_charset($link, "utf8");

  $query0 = time_journal_query_reasons($link);

  $merr = db_error($link);
  if ( !$query0 ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return array();
  }                     

  $results = Array();
 
  while ( $row0 = db_fetch_one($query0) )
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
  db_set_charset($link, "utf8");

  $query = time_journal_query_add_work_for_period($link, $userID, $startDTStr, $stopDTStr);

  $merr = db_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return array();
  }                     

  $results = Array();
 
  while ( $row = db_fetch_one($query) ){
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
