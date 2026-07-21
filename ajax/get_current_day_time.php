<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";

$cur_ssid = session_id();

if ( isset($_SESSION['ss_sessid']) &&  $_SESSION['ss_sessid'] == $cur_ssid )
{
    $dateTimeStr = get_current_datetime_in_timezone_str( 1, 0 );

    echo "<h1 class=\"clock\">".$dateTimeStr."</h1>";
}
else
{
    echo $cur_ssid. "---";
}
  
?>