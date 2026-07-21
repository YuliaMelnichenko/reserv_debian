<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
require_once __DIR__ . "/../inc/notification_summary.php";
include __DIR__ . "/../php_tori/connect.php";

$notifCount = 0;

if ( isset( $_SESSION['ss_id'] ) ){
  $counts = get_supervisor_notification_counts(
    $link,
    (int)$_SESSION['ss_id'],
    get_current_datetime_in_timezone_str(1, 0)
  );

  if ($counts === false) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  $notifCount = $counts['delay_count'];
}

$notifCountStr = $notifCount > 0 ? "($notifCount)" : "";
echo "<h5 class=\"biggersmall\">По опозданиям $notifCountStr</h5>";
?>
