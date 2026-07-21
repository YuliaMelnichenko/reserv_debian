<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";

$notifCount = "";

if ( isset( $_SESSION['ss_id'] ) )
{ 
  $notificationCount = 0;
  $currentDayNotificationCount = 0;
  $notifCount = get_pause_notif_counts( $_SESSION['ss_id'], $notificationCount, $currentDayNotificationCount );
  if ( $currentDayNotificationCount > 0 )
    $notifCountStr = "($currentDayNotificationCount)";    
}

echo "<h5 class=\"biggersmall\">По приостановкам учета времени $notifCountStr</h5>";
?>