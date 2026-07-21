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

function get_dbsetup_param( $paramName ) {
  include __DIR__ . "/php_tori/connect.php";

  mysqli_set_charset($link, "utf8");     

  $success = 0;
  $valInt = 0;
  $valFloat = 0;
  $valStr = '';
  $query = db_query(
    $link,
    "SELECT valueInt, valueFloat, valueStr FROM DBSETUP WHERE paramName = ?",
    's',
    array($paramName)
  );

  if ( !$query ) 
  {
    $success = 0;
  }
  else
  {
    $vn = mysqli_num_rows($query);
    if ( $vn == 1 )
    {  
      $row0 = mysqli_fetch_array($query, MYSQLI_ASSOC);
      $valInt = $row0["valueInt"]; 	
      $valFloat = $row0["valueFloat"]; 	
      $valStr = $row0["valueStr"]; 	
      $success = 1;
    }
  }

  return array( $success, $valInt, $valFloat, $valStr );
}  

function get_sv_name_by_userid( $user_id )
{
  include __DIR__ . "/php_tori/connect.php";

  $query0 = db_query($link, "SELECT SUPERVISORID FROM GROUPS WHERE TYPE = 100 and USERID = ?", 'i', array((int)$user_id));

  $merr=mysqli_error($link);
  if ( !$query0 ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    $vn=mysqli_num_rows($query0);
    if ( $vn >= 1 )
    {  
      $row0 = mysqli_fetch_array($query0, MYSQLI_ASSOC);
      $svID = $row0["SUPERVISORID"]; 	
    }
    else
    {
      return "";
    }
  } 	

  $query = db_query($link, "SELECT FIRSTNAME, LASTNAME, SURNAME FROM employees WHERE ID = ?", 'i', array((int)$svID));
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    $vn=mysqli_num_rows($query);
    if ( $vn == 1 )
    {  
      $row = mysqli_fetch_assoc($query);
      return $row["SURNAME"]." ".$row["FIRSTNAME"]." ".$row["LASTNAME"];
    }
    else
    {
      return "Unknown. Error 2";
    }
  } 
}  

function get_group_user_info_by_svID_for_report_ex( $svID ){
  include __DIR__ . "/php_tori/connect.php";

  $userIDs=array();

  mysqli_set_charset($link, "utf8");

  $dirID = 0;

  if (isset($_SESSION["ss_id"])) {
    $dirID = $_SESSION["ss_id"];
    if ($dirID != 1) {
      if ( $svID !=-1 ){
        $query0 = db_query($link, "SELECT USERID FROM GROUPS WHERE SUPERVISORID = ? and (TYPE = 0 or TYPE = -1) GROUP BY USERID", 'i', array((int)$svID));
      }
      else{
        $query0 = db_query($link, "SELECT USERID FROM GROUPS WHERE TYPE = 0 or TYPE = -1 GROUP BY USERID");
      }
      $merr=mysqli_error($link);
      if ( !$query0 ) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
      }
      else{
        $vn=mysqli_num_rows($query0);
        if ($vn == 0){
          $userIDs[] = $svID;
        }
        else{
          while($row = mysqli_fetch_array($query0, MYSQLI_ASSOC)){
            $userIDs[] = $row["USERID"];  
          }
        }
      }
    }
    else{
      if ( $svID !=-1 ){
        $query0 = db_query($link, "SELECT e.id FROM GROUPS g INNER JOIN employees e ON g.USERID = e.id WHERE g.SUPERVISORID = ? AND (g.TYPE = 0 OR g.TYPE = -1) ORDER BY e.surname", 'i', array((int)$svID));
      }
      else{
        $query0 = db_query($link, "SELECT e.id FROM GROUPS g INNER JOIN employees e ON g.USERID = e.id WHERE g.TYPE = 0 OR g.TYPE = -1 ORDER BY e.surname");
      }
      $merr=mysqli_error($link);
      if ( !$query0 ) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
      }
      else{
        $vn=mysqli_num_rows($query0);
        if ($vn == 0){
          $userIDs[] = $svID;
        }
        else{
          while($row = mysqli_fetch_array($query0, MYSQLI_ASSOC)){
            $userIDs[] = $row["id"];
          }
        }
      }
    }
  }

  $newUserIDs=array();

  $ownUserID = -1;

  if ( isset( $_SESSION['ss_id'] ) )
  {
    $ownUserID = $_SESSION['ss_id'];
    if ( $ownUserID != 500 & $ownUserID != 501 )
    {
        $newUserIDs[] = $ownUserID;
    }
  }

  foreach ($userIDs as $val)
  {
    if ( $val != $ownUserID )
    {
      $newUserIDs[] = $val;
    }
  }

  $usersRate=array();
  $usersFIO=array();
  $usersNameParts=array();
  
  foreach ( $newUserIDs as $ID )
  {
    $query = db_query($link, "SELECT rate, firstname, lastname, surname FROM employees WHERE ID = ?", 'i', array((int)$ID));
    $merr=mysqli_error($link);
    if ( !$query ) 
    {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
    }
    else
    {
      $vn=mysqli_num_rows($query);
      if ( $vn == 1 )
      {  
        $row = mysqli_fetch_array($query, MYSQLI_ASSOC);
 	      $usersRate[] = $row["rate"];
        $surname = isset($row["surname"]) ? $row["surname"] : "";
        $firstname = isset($row["firstname"]) ? $row["firstname"] : "";
        $lastname = isset($row["lastname"]) ? $row["lastname"] : "";

        $usersFIO[] = trim($surname . " " . $firstname . " " . $lastname);
        $usersNameParts[] = array(
          "surname" => $surname,
          "firstname" => $firstname,
          "lastname" => $lastname,
        );
      }
    }
  }
  //foreach ($usersFIO as $val){ echo "$val\n"; } echo "<br>";

  $usersInfo = array();
  $usersInfo[0] = $newUserIDs;
  $usersInfo[1] = $usersFIO;
  $usersInfo[2] = $usersRate;
  $usersInfo[8] = $usersNameParts;

  return $usersInfo;
}  

function journal_status_label($text, $class = "middleBold_r")
{
  return "<h5 class=\"" . html_escape($class) . "\">" . html_escape($text) . "</h5>";
}

function GetHourNormByMonth( $date, $rate ){
  include __DIR__ . "/php_tori/connect.php";

  $duration = 0;

  $query0 = db_query($link, "SELECT dur40, dur36, dur24 FROM factory_calendar WHERE date = ?", 's', array($date));

  $merr=mysqli_error($link);
  if ( !$query0 ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
   
    $vn=mysqli_num_rows($query0);
    if ( $vn > 1 )
    {  
      return "Error 378. Dublicate factory calendar dates";
    }
    else
    {
      $row0 = mysqli_fetch_array($query0,MYSQLI_ASSOC);
      if ( $rate == 40 ){ $duration = $row0["dur40"]; }	
      if ( $rate == 36 ){ $duration = $row0["dur36"]; }	
      if ( $rate == 24 ){ $duration = $row0["dur24"]; }	
    }  
  }
  return $duration; 	
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function am_i_superuser( $userID ) {
  include __DIR__ . "/php_tori/connect.php";

  $query = db_query($link, "SELECT 1 FROM GROUPS WHERE SUPERVISORID = ? and TYPE <> -1 LIMIT 1", 'i', array((int)$userID));
  $merr = mysqli_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return 0;
  }

  $vn = mysqli_num_rows( $query );

  if ( $vn > 0 ){
    return 1;
  }
  return 0;
}

function get_notification_count( $user_id ){
  include __DIR__ . "/php_tori/connect.php";

  $currentDate = get_current_datetime_in_timezone_str( 1, 0 );

  $paramArr = get_dbsetup_param( 'add_time_journal_deep_day' );
  
  $paramInt = (-1)*$paramArr[1];
  
  $query = time_journal_query_pending_add_time($link, $user_id, $currentDate, $paramInt);

  $merr=mysqli_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    return mysqli_num_rows($query);
  }
}

function get_delay_notification_count( $user_id ){
  include __DIR__ . "/php_tori/connect.php";

  $currentDate = get_current_datetime_in_timezone_str( 1, 0 );

  $paramArr = get_dbsetup_param( 'delay_journal_deep_day' );
  
  $paramInt = (-1)*$paramArr[1];
  
  $query = time_journal_query_pending_delays($link, $user_id, $currentDate);

  $merr=mysqli_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    return mysqli_num_rows($query);
  }
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function get_workdays_holidays_bay_range( $startDate, $stopDate )
{
  $dates = array();
  $types = array();
  include __DIR__ . "/php_tori/connect.php";

  $query = db_query($link, "SELECT distinct DATE, TYPE FROM work_dayoff where date >= ? and date <= ?", 'ss', array($startDate, $stopDate));
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {
      $dates[] = $row["DATE"];
      $types[] = $row["TYPE"];
    }
  }
  $result = array();
  $result[0] = $dates;
  $result[1] = $types;
 
  return $result;
}

function get_holidays()
{
  $holidays = array();
  $index = 1;
  include __DIR__ . "/php_tori/connect.php";

  $query = db_query($link, "SELECT DATE FROM work_dayoff where type = 0");
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {
      $holidays[$index] = $row["DATE"];
      $index = $index + 1;
    }
  }
  return $holidays;
}

function get_work_day()
{
  $workDays = array();
  $index = 1;
  include __DIR__ . "/php_tori/connect.php";

  $query = db_query($link, "SELECT DATE FROM work_dayoff where type = 1");
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {
      $workDays[$index] = $row["DATE"];
      $index = $index + 1;
    }
  }
  return $workDays;
}

function get_days_range( $startDate, $stopDate )
{
  $daysRange = array();
  $idx = 1;


  for ( $date = $startDate; ; $date = DayIncDN( $date, 1 ) )
  {
    $daysRange[$idx] = $date;
    $idx ++;  

        
    if ( $date == $stopDate )
    {
      break;
    }
  }
  return $daysRange;
}

function get_days_wo_weekends( $daysRange )
{
  $days = array();
  $idx = 1;

  for ( $idx1 = 1; $idx1 <= count( $daysRange ); $idx1 ++ )
  {
    if ( ! isWeekEnd( $daysRange[$idx1] ) )
    {
      $days[$idx] = $daysRange[$idx1];
      $idx ++;  
    }
  }

  return $days;
}

function get_days_wo_holidays( $daysRange )
{
  $holidays = get_holidays();

  $days = array();
  $idx = 1;

  for ( $idx1 = 1; $idx1 <= count( $daysRange ); $idx1 ++ )
  {
    $found = 0;

    for ( $idx2 = 1; $idx2 <= count( $holidays ); $idx2 ++ )
    {

      if ( $daysRange[$idx1] == $holidays[$idx2] )
      {
        $found = 1;
        break;
      }
    }
    if ( $found == 0 )
    {
      $days[$idx] = $daysRange[$idx1];
      $idx ++;  
    }
  }

  return $days;
}      

function get_days_with_add_workdays( $daysRange )
{
  $workDays = get_work_day();

  $days = $daysRange;
  $idx = count($daysRange) + 1;

  for ( $idx1 = 1; $idx1 <= count( $workDays ); $idx1 ++ )
  {
    $found = 0;

    for ( $idx2 = 1; $idx2 <= count( $daysRange ); $idx2 ++ )
    {
      if ( $workDays[$idx1] == $daysRange[$idx2] )
      {
        $found = 1;
        break;
      }
    }
    if ( $found == 1 )
    {
      $days[$idx] = $workDays[$idx1];
      $idx ++;  
    }
  }
  return $days;
}

function max_date( $daysRange )
{
  if ( count($daysRange) == 0 )
    return "";
  $maxDate = $daysRange[1];
  for ( $idx1 = 1; $idx1 <= count( $daysRange ); $idx1 ++ )
  {
    if( strtotime( $daysRange[$idx1] ) > strtotime( $maxDate ) )
    {
      $maxDate = $daysRange[$idx1];
    }
  }                                
  return $maxDate;  
}

function min_date( $daysRange )
{
  if ( count($daysRange) == 0 )
    return "";
  $minDate = $daysRange[1];
  for ( $idx1 = 1; $idx1 < count( $daysRange ); $idx1 ++ )
  {
    if( strtotime( $daysRange[$idx1] ) < strtotime( max_date( $daysRange ) ) )
    {
      $minDate = $daysRange[$idx1];
    }
  }                                
  return $minDate;  
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


function get_user_rate( $userID ){
  include __DIR__ . "/php_tori/connect.php";

  $rate = 40;

  $query = db_query($link, "SELECT RATE FROM employees where ID = ?", 'i', array((int)$userID));

  $merr=mysqli_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    if ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) ){
      $rate = $row["RATE"];
    }
  }

  return $rate; 
}


function get_norm_by_range_sec( $startDate, $stopDate, $userID ){
  include __DIR__ . "/php_tori/connect.php";

  $rate = 40;

  $query = db_query($link, "SELECT RATE FROM employees where ID = ?", 'i', array((int)$userID));

  $merr=mysqli_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) ){
      $rate = $row["RATE"];
    }
  }

  $daysRange = get_days_range( $startDate, $stopDate );
  $daysRange = get_days_wo_weekends( $daysRange );
  $daysRange = get_days_wo_holidays( $daysRange );
  $daysRange = get_days_with_add_workdays( $daysRange );
  $normaByDay = $rate / 5;
  $daysCount = count($daysRange);

  $normByRange = $normaByDay * $daysCount * 60 * 60;

  return $normByRange; 
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


function get_norm_time_by_current_day_sec( $user_defaultStartHour, $user_defaultStartMinute ){
  $hours = date("H");
  $minutes = date("i");
  $seconds = date("s");

  $currentTime = $hours * 60 + $minutes;
  $defaultStartTime = $user_defaultStartHour * 60 + $user_defaultStartMinute;
  $result = ( $currentTime - $defaultStartTime )*60 + $seconds;
  return $result;
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

function get_superuser_names_by_user_id( $ID )
{
  include __DIR__ . "/php_tori/connect.php";  
  mysqli_set_charset($link, "utf8");

  $ret = Array();

  $query = db_query($link, "SELECT DISTINCT ID, FIRSTNAME, LASTNAME, SURNAME FROM employees WHERE ID in (select SUPERVISORID from GROUPS where userid = ?)", 'i', array((int)$ID));
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    while( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {
      $tempArray = Array();
      $tempArray[] = $row["SURNAME"]." ".$row["FIRSTNAME"]." ".$row["LASTNAME"];
      $tempArray[] = $row["ID"];
      $ret[] = $tempArray;
    }
  } 
  return $ret;
}  


function get_superuser_name_by_id( $suID )
{
  return get_user_name_by_id( $suID );
}  

function get_user_name_by_id( $suID )
{
  include __DIR__ . "/php_tori/connect.php";  
  mysqli_set_charset($link, "utf8");

  $query = db_query($link, "SELECT FIRSTNAME, LASTNAME, SURNAME FROM employees WHERE ID = ?", 'i', array((int)$suID));
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    $vn=mysqli_num_rows($query);
    if ( $vn == 1 )
    {  
      $row = mysqli_fetch_array($query, MYSQLI_ASSOC);
      return $row["SURNAME"]." ".$row["FIRSTNAME"]." ".$row["LASTNAME"];
    }
    else
    {
      return "";
    }
  } 
  return "";
}  

function get_pause_agree_able_superusers_by_userID( $userID )
{
  include __DIR__ . "/php_tori/connect.php";  
  mysqli_set_charset($link, "utf8");

  $query0 = db_query($link, "SELECT SUPERVISORID FROM GROUPS where USERID = ? and type = 3", 'i', array((int)$userID));

  $rets = Array();

  while ( $row0 = mysqli_fetch_array($query0, MYSQLI_ASSOC) )
  {  
    $SUID = $row0["SUPERVISORID"];

    $SUName = get_superuser_name_by_id( $SUID );
    
    $tempArray = Array(); 

    $tempArray[0] = $SUID;
    $tempArray[1] = $SUName;

    $rets[] = $tempArray;
  }
  return $rets;
}

function get_users_by_superusers_and_type( $SUID, $type )
{
  include __DIR__ . "/php_tori/connect.php";  
  mysqli_set_charset($link, "utf8"); 

  $query0 = db_query($link, "SELECT g.USERID FROM GROUPS g inner join employees e on g.userid = e.id where g.SUPERVISORID = ? and g.type = ? order by e.SURNAME asc", 'ii', array((int)$SUID, (int)$type));

  $rets = Array();

  while ( $row0 = mysqli_fetch_array($query0, MYSQLI_ASSOC) )
  {  
    $UID = $row0["USERID"];

    $rets[] = $UID;
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

function get_user_defStartTime_and_allowedDelay( $USERiD, &$user_defaultStartTime, &$user_allowedDelay )
{
  include __DIR__ . "/php_tori/connect.php";  

  $query = db_query($link, "SELECT defaultStartTime, AllowedDelayMinutes FROM employees where ID = ?", 'i', array((int)$USERiD));

  $ret = 0;

  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    if ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {
      $user_defaultStartTime = $row["defaultStartTime"];
      $user_allowedDelay = $row["AllowedDelayMinutes"];
      $ret = 1;
    }
  }
  return $ret;  
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

function get_and_update_start_time_status( $userID ){                
  include __DIR__ . "/php_tori/connect.php";

  $dtArr = get_splited_current_date_time_in_timezone();

  $currentTimeHHMMSS = $dtArr[1]; 

  $isThereDelay = 0;
  $ss_defaultStartTime = '';
  $ss_allowedDelay = 0;
  $ss_defaultStartTimeWithDelay = '';
  $ss_defaultStartTimeWithDelayVal = 0;
  $ss_RemoteWork = 0;

  $query = db_query($link, "SELECT defaultStartTime, allowedDelayMinutes, remoteWork FROM employees WHERE id = ?", 'i', array((int)$userID));
  $merr = mysqli_error($link);

  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    $vn=mysqli_num_rows($query);
    
    if ( $vn == 1 ){ 
      $row = mysqli_fetch_assoc($query);
      $ss_defaultStartTime = $row["defaultStartTime"];	
      $ss_allowedDelay = $row["allowedDelayMinutes"];
      $ss_RemoteWork = $row["remoteWork"];

      $_SESSION['ss_RemoteWorkStr'] = "В ОФИСЕ";
      $_SESSION['ss_RemoteWork'] = 0;

      if ( $ss_RemoteWork == 1 ){
        $_SESSION['ss_RemoteWork'] = 1;
        $_SESSION['ss_RemoteWorkStr'] = "УДАЛЕННЫЙ";
      }

      $_SESSION['ss_defaultStartTime'] = $ss_defaultStartTime;
      $_SESSION['ss_allowedDelay'] = $ss_allowedDelay;
      
      $ss_defaultStartTimeWithDelay = date("H:i:s", strtotime($ss_defaultStartTime." + ".$ss_allowedDelay." minute"));
   
      $_SESSION['ss_defaultStartTimeWithDelay'] = $ss_defaultStartTimeWithDelay;
      $ss_defaultStartTimeWithDelayVal = strtotime($ss_defaultStartTimeWithDelay);
      $_SESSION['ss_defaultStartTimeWithDelayVal'] = $ss_defaultStartTimeWithDelayVal;

      if ( $currentTimeHHMMSS <= $ss_defaultStartTimeWithDelayVal || $ss_RemoteWork == 1 ){
        $isThereDelay = 0;
      }
      else{
        $isThereDelay = 2;
      }

      $_SESSION['ss_there_is_delay'] = $isThereDelay;
    }
  }  
  return array( $isThereDelay, $ss_defaultStartTime, $ss_allowedDelay, $ss_defaultStartTimeWithDelay, $ss_defaultStartTimeWithDelayVal, $ss_RemoteWork );
}

function apply_staff_leaves_to_days_norm($link, $userID, $startDate, $stopDate, $days_dates_set, $days_norm)
{
  $query = db_query($link, "
    SELECT start_date, stop_date, event
    FROM staff_leaves
    WHERE user_id = ?
      AND event IN ('Отпуск', 'Больничный')
      AND start_date <= ?
      AND stop_date >= ?
  ", 'iss', array((int)$userID, $stopDate, $startDate));

  $merr = mysqli_error($link);
  if (!$query) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return $days_norm;
  }

  while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
    $leaveStart = $row["start_date"];
    $leaveStop = $row["stop_date"];

    if ($leaveStart < $startDate) {
      $leaveStart = $startDate;
    }

    if ($leaveStop > $stopDate) {
      $leaveStop = $stopDate;
    }

    for ($idx = 0; $idx < count($days_dates_set); $idx++) {
      $day = $days_dates_set[$idx];

      if ($day >= $leaveStart && $day <= $leaveStop) {
        $days_norm[$idx] = 0;
      }
    }
  }

  return $days_norm;
}

function get_staff_leave_events_by_days($link, $userID, $startDate, $stopDate, $days_dates_set)
{
  $leaveEvents = array_fill(0, count($days_dates_set), "NDF");

  $query = db_query($link, "
    SELECT start_date, stop_date, event
    FROM staff_leaves
    WHERE user_id = ?
      AND event IN ('Отпуск', 'Больничный')
      AND start_date <= ?
      AND stop_date >= ?
  ", 'iss', array((int)$userID, $stopDate, $startDate));

  if (!$query) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return $leaveEvents;
  }

  while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
    $leaveStart = max($row["start_date"], $startDate);
    $leaveStop = min($row["stop_date"], $stopDate);
    $event = $row["event"];

    for ($idx = 0; $idx < count($days_dates_set); $idx++) {
      $day = $days_dates_set[$idx];

      if ($day >= $leaveStart && $day <= $leaveStop) {
        $leaveEvents[$idx] = $event;
      }
    }
  }

  return $leaveEvents;
}

function get_work_dayoff_types_by_range($link, $startDate, $stopDate)
{
  $result = array();

  $query = db_query($link, "
    SELECT date, type
    FROM work_dayoff
    WHERE date >= ?
      AND date <= ?
      AND type IN (0, 1, 2)
  ", 'ss', array($startDate, $stopDate));

  if (!$query) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return $result;
  }

  while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
    $result[$row["date"]] = (int)$row["type"];
  }

  return $result;
}

?>
