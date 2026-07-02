<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

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