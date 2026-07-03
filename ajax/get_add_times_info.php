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

$startDate = $_POST['startDate'];
$stopDate = $_POST['stopDate'];
$userID = $_POST['userID'];

$addRets = get_add_work_info_by_user_and_day_range( $userID, $startDate, $stopDate );

echo "<table class=\"hor_bor\" border=0>";
  echo "<tr>";
    echo "<td valign=\"middle\" align=\"left\" width = 270>";
      echo "<h5 class=\"big\">Работа вне офиса</h5>";
    echo "</td>";
    echo "<td valign=\"middle\" align=\"right\" width = 275>";
      echo "<img onclick=\"close_add_time_list();\" src=\"img/close.png\">";
    echo "</td>";
  echo "</tr>";
echo "</table>";

echo "<table class=\"hor_bor\" border=1>";
  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 70>";
      echo "<h5 class=\"middle1\">Длительность"."</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 165>";
      echo "<h5 class=\"middle1\">Основание"."</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 210>";
      echo "<h5 class=\"middle1\">Комментарий"."</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 100>";
      echo "<h5 class=\"middle1\">Статус"."</h5>";
    echo "</td>";  
  echo "</tr>";

foreach( $addRets as $addRet )
{
  $startTime = $addRet[0];
  $stopTime = $addRet[1];
  $reason = $addRet[2];
  $description = $addRet[3];
  $approved = $addRet[4];
  $suid = $addRet[5];
  $timeDuration = $addRet[6];
  $timeDurationStr = format_time_d_hhmm_pure( $timeDuration );

  if ( $addRet[7] == 1 ){ continue; } 
  if ( $addRet[4] == 99 OR $addRet[4] == 100 OR $addRet[4] == 101 ){ continue; } 

  if ( $reason == 1 ){ $reasonStr = "Доставка комплектующих,<br>оборудования и т.д."; }
  else if ( $reason == 2 ){ $reasonStr = "Работа не в офисе"; }
  else if ( $reason == 3 ){ $reasonStr = "Работа на дому"; }
  else if ( $reason == 4 ){ $reasonStr = "Прочее"; }
  else if ( $reason == 5 ){ $reasonStr = "Командировка"; }
  else if ( $reason == 7 ){ $reasonStr = "Болезнь"; }
  else if ( $reason == 4 OR $reason == 8 ){ $reasonStr = "Прочее"; }

  $superUserName = get_superuser_name_by_id( $suid );
    
  $bgColor = "";
  
  if ( $approved == 0 ){ $approvedStr = "на рассмотрении"; $fontSt = "small"; }
  else if ( $approved == -1 )
  { 
    $addMsg1 = " <img title=\"Решение принял: $superUserName\" src=\"img/superuserBad.png\">";
    $approvedStr = "отклонено $addMsg1"; $bgColor = "#FFAAAA";  
  }
  else if ( $approved == 1 )
  { 
    $addMsg1 = " <img title=\"Решение принял: $superUserName\" src=\"img/superuserGood.png\">";
    $approvedStr = "принято $addMsg1"; $bgColor = "#AAFFAA";
  }


  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 70>";
      echo "<h5 class=\"small1\">($timeDurationStr)<br>[$startTime-$stopTime]"."</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 165>";
echo "<h5 class=\"small1\">" . html_escape($reasonStr) . "</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 210>";
echo "<h5 class=\"small1\">" . html_escape($description) . "</h5>";
    echo "</td>";  
    echo "<td bgcolor=\"$bgColor\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 100>";
      echo "<h5 class=\"$fontSt\">$approvedStr"."</h5>";
    echo "</td>";  
  echo "</tr>";
}

echo "</table><br>";

?>
