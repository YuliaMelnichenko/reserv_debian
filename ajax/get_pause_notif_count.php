<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
require_once __DIR__ . "/../inc/notification_summary.php";
include __DIR__ . "/../php_tori/connect.php";

$notifCountStr = "";

if ( isset( $_SESSION['ss_id'] ) )
{
  $counts = get_pause_notification_count(
    $link,
    (int)$_SESSION['ss_id'],
    get_current_datetime_in_timezone_str(1, 0)
  );

  if ($counts === false) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  if ( $counts['current_day_count'] > 0 )
    $notifCountStr = "(" . $counts['current_day_count'] . ")";
}

echo "<h5 class=\"biggersmall\">По приостановкам учета времени $notifCountStr</h5>";
?>
