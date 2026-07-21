<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";

$notifCount = "";

if ( isset( $_SESSION['ss_id'] ) ){  
  $notifCount = get_notification_count( $_SESSION['ss_id'] );
  if ( $notifCount > 0 ){
    $notifCountStr = "($notifCount)";  
    echo "<h5 class=\"biggersmall\">По работе вне офиса $notifCountStr</h5>";
  }
}
?>