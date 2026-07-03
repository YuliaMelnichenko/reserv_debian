<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_POST['userID'];
$inTime = $_POST['inTime'];

$user_defaultStartTime = 0;
$user_allowedDelay = 0;

include_once __DIR__ . "/../funcs.php";

get_user_defStartTime_and_allowedDelay( $userID, $user_defaultStartTime, $user_allowedDelay );

$newInTimeVal = strtotime($inTime);
$inTimeVal = strtotime($user_defaultStartTime) + $user_allowedDelay * 60;

if ( $newInTimeVal > $inTimeVal )
{
  echo "1";
}
else
{
  echo 0;
}  
                         
?>                                                                   