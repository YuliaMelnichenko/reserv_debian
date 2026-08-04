<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = request_post_int('userID');
$inTime = request_post_time('inTime');

if ($userID <= 0) {
  deny_ajax_access(400, 'INVALID_USER');
}

if ($inTime === null) {
  deny_ajax_access(400, 'INVALID_TIME');
}

require_ajax_self_or_supervisor($userID, 3);

$user_defaultStartTime = 0;
$user_allowedDelay = 0;

include_once __DIR__ . "/../funcs.php";

get_user_defStartTime_and_allowedDelay( $userID, $user_defaultStartTime, $user_allowedDelay );

$delayArr = get_delay_value($inTime, $user_defaultStartTime, $user_allowedDelay);

if ( $delayArr[0] == 1 )
{
  echo "1";
}
else
{
  echo 0;
}  
                         
?>
