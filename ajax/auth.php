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
$__password = request_post_trimmed_string('passwd');
$__rememberLogin = request_post_string('remember_login') === '1';

db_set_charset($link, "utf8");

$query = auth_find_employee_by_login($link, $__login);
$merr = db_error($link);

if ( !$query ) 
{
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
}
else
{
  $vn = db_num_rows($query);

  if ( $vn == 1 )
  { 
    $row = db_fetch_one($query);
    $passwordVerification = auth_verify_employee_password($row, $__password);

    if (!$passwordVerification['is_valid']) {
      $vn = 0;
    }
    else {
      if ($passwordVerification['needs_hash_upgrade']
        && !auth_upgrade_employee_password_hash($link, (int) $row['id'], $__password)) {
        error_log('[TORI] Password hash upgrade failed for employee ' . (int) $row['id']);
      }

      auth_start_employee_session($row);
      csrf_rotate_token();
      if ($__rememberLogin) {
        auth_issue_remember_token($link, (int) $row['id']);
      }
      else {
        auth_revoke_remember_token($link);
      }
      echo "OK";
    }
  }
  if ( $vn != 1 )
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
    auth_revoke_remember_token($link);
    session_destroy();
  }
//header("Location: index.php");
//exit(); 
} 	
?>
