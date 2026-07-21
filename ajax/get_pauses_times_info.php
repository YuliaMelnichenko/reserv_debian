<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID_ = $_SESSION['ss_id']; 
$currentDate = date('Y-m-d');

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$startDate = (string) ($_POST['startDate'] ?? '');
$stopDate = (string) ($_POST['stopDate'] ?? '');
$userID = (int) ($_POST['userID'] ?? 0);

if ($userID <= 0) {
  deny_ajax_access(400, 'INVALID_USER');
}

require_ajax_self_or_superuser($userID);

$rangeStart = normalize_date_value($startDate);
$rangeStop = normalize_date_value($stopDate);

if ($rangeStart === null || $rangeStop === null || $rangeStop < $rangeStart) {
  deny_ajax_access(400, 'INVALID_DATE_RANGE');
}

$rangeStart .= ' 00:00:00';
$rangeStop = date('Y-m-d 00:00:00', strtotime($rangeStop . ' +1 day'));
$addRets = get_add_work_info_by_user_and_day_ex($userID, $rangeStart, $rangeStop, 0);

echo "<table class=\"hor_bor\" border=0>";
  echo "<tr>";
    echo "<td valign=\"middle\" align=\"left\" width = 400>";
      echo "<h5 class=\"big\">Продолжительность приостановки учета времени</h5>";
    echo "</td>";
    echo "<td valign=\"middle\" align=\"right\" width = 145>";
      echo "<img onclick=\"close_pause_time_list();\" src=\"img/close.png\">";
    echo "</td>";
  echo "</tr>";
echo "</table>";

echo "<table class=\"hor_bor\" border=1>";
  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 70>";
      echo "<h5 class=\"middle1\">Длительность"."</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 175>";
      echo "<h5 class=\"middle1\">С кем согласовано"."</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 300>";
      echo "<h5 class=\"middle1\">Комментарий"."</h5>";
    echo "</td>";  
  echo "</tr>";

foreach( $addRets as $addRet )
{
  $startTime = datetime_to_time_str($addRet[0]);
  $stopTime = datetime_to_time_str($addRet[1]);
  $reason = $addRet[2];
  $description = $addRet[3];
  $approved = $addRet[4];
  $suid = $addRet[5];
  $timeDuration = $addRet[6];

  if ( $addRet[7] == 0 ){ continue; } 

  $timeDurationStr = format_time_d_hhmm_pure( $timeDuration );

  $superUserName = get_superuser_name_by_id( $suid );
    
  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 70>";
      echo "<h5 class=\"small1\">($timeDurationStr)<br>[$startTime-$stopTime]"."</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 165>";
echo "<h5 class=\"small1\">" . html_escape($superUserName) . "</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 210>";
echo "<h5 class=\"small1\">" . html_escape($description) . "</h5>";
    echo "</td>";  
  echo "</tr>";
}

echo "</table><br>";

?>
