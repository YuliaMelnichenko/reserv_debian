<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/ajax_response.php';
require_once __DIR__ . '/../inc/request.php';
ajax_text_headers();
                
include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";
require_once __DIR__ . "/../inc/access.php";
require_csrf_for_unsafe_request(true);

$__login = request_post_trimmed_string('login');
$__passwd = md5(md5(request_post_trimmed_string('passwd')));

mysqli_set_charset($link, "utf8");

$query = db_query(
  $link,
  'SELECT id, rate, defaultStartTime, allowedDelayMinutes, userTimeZoneMins, dayTransitionTime, remoteWork FROM employees WHERE login = ? AND passwd = ?',
  'ss',
  array($__login, $__passwd)
);
$merr = mysqli_error($link);

if ( !$query ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else
{
  $vn = mysqli_num_rows($query);

  if ( $vn == 1 )
  { 
    $row = mysqli_fetch_assoc($query);
    session_regenerate_id(true);
    $_SESSION['ss_id'] = $row["id"];
    $_SESSION['ss_rate'] = $row["rate"];
    $ss_defaultStartTime = $row["defaultStartTime"];	
    $_SESSION['ss_defaultStartTime'] = $ss_defaultStartTime;
    $defaultStartHour = (int)(date("H", strtotime($ss_defaultStartTime)));
    $defaultStartMinute = (int)(date("i", strtotime($ss_defaultStartTime)));      
    $ss_allowedDelay = $row["allowedDelayMinutes"];
    $_SESSION['ss_allowedDelay'] = $ss_allowedDelay;
    $ss_defaultStartTimeWithDelay = date("H:i:s", strtotime($ss_defaultStartTime." + ".$ss_allowedDelay." minute"));

    $_SESSION['ss_defaultStartTimeWithDelay'] = $ss_defaultStartTimeWithDelay;
    $_SESSION['ss_defaultStartTimeWithDelayVal'] = strtotime($ss_defaultStartTimeWithDelay);
    $_SESSION['ss_defaultStartHour'] = $defaultStartHour;
    $_SESSION['ss_defaultStartMinute'] = $defaultStartMinute;
    $_SESSION['ss_mode'] = 1;
    $_SESSION['ss_delay_show_save'] = 0;
    $_SESSION['ss_UserTimeZoneMins'] = $row["userTimeZoneMins"];
    $_SESSION['ss_sessid'] = session_id();
    csrf_rotate_token();
    $retArr = get_current_datetime_in_timezone();
    $_SESSION['ss_UserTimeZoneStr'] = $retArr[5];
    $ss_dayTransitionTime = get_standard_day_transition_time();
    $_SESSION['ss_dayTransitionTime'] = $ss_dayTransitionTime;
    

    $ss_RemoteWork = $row["remoteWork"];
    $_SESSION['ss_RemoteWorkStr'] = "В ОФИСЕ";
    $_SESSION['ss_RemoteWork'] = 0;
    if ( $ss_RemoteWork == 1 )
    {
      $_SESSION['ss_RemoteWork'] = 1;
      $_SESSION['ss_RemoteWorkStr'] = "УДАЛЕННЫЙ";
    }
    $_SESSION['ss_visiting_ID'] = -1;      

    $_SESSION['rep_start_stop_date_mode'] = 2;

    $_SESSION['ss_dayWasChanged'] = 0;
    echo "OK";
  }
  else
  {
    echo "Ошибка авторизации! Неправильный логин/пароль";
    unset($_SESSION['ss_id']);
    unset($_SESSION['ss_rate']);
    unset($_SESSION['ss_defaultStartTime']);
    unset($_SESSION['ss_defaultStartTimeWithDelay']);
    unset($_SESSION['ss_defaultStartTimeWithDelayVal']);
    unset($_SESSION['ss_defaultStartHour']);
    unset($_SESSION['ss_defaultStartMinute']);    
    unset($_SESSION['ss_allowedDelay']);
    unset($_SESSION['ss_mode']);          
    unset($_SESSION['ss_delay_show_save']);          
    unset($_SESSION['ss_UserTimeZoneMins']);
    unset($_SESSION['ss_UserTimeZoneStr']);
    unset($_SESSION['ss_dayTransitionTime']);
    unset($_SESSION['ss_sessid']); 
    unset($_SESSION['ss_RemoteWork']);
    unset($_SESSION['ss_RemoteWorkStr']);
    unset($_SESSION['ss_visiting_ID']);
    session_destroy();
  }
//header("Location: index.php");
//exit(); 
} 	
?>
