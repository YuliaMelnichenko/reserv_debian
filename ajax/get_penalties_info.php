<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID_ = $_SESSION['ss_id']; 

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$startDate = $_POST['startDate'];
$stopDate = $_POST['stopDate'];
$userID = $_POST['userID'];
$user_defaultStartTime = 0;
$user_allowedDelay = 0;


if ( get_user_defStartTime_and_allowedDelay( $userID, $user_defaultStartTime, $user_allowedDelay ) == 0 )
{
  echo "Ошибка получения сведений по пользователю $userID";  
}
else
{
  $delayRets = get_delay_info_by_user_and_day_range( $userID, $startDate, $stopDate, $user_defaultStartTime, $user_allowedDelay );

  echo "<table class=\"hor_bor\" border=0>";
    echo "<tr>";
      echo "<td valign=\"middle\" align=\"left\" width = 270>";
        echo "<h5 class=\"big\">Опоздания</h5>";
      echo "</td>";
      echo "<td valign=\"middle\" align=\"right\" width = 255>";
        echo "<img onclick=\"close_penalties_list();\" src=\"img/close.png\">";
      echo "</td>";
    echo "</tr>";
  echo "</table>";

  echo "<br><table class=\"hor_bor\" border=1>";
    echo "<tr>";
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 135>";
        echo "<h5 class=\"middle1\">Длительность"."</h5>";
      echo "</td>";  
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 240>";
        echo "<h5 class=\"middle1\">Объяснение"."</h5>";
      echo "</td>";  
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 150>";
        echo "<h5 class=\"middle1\">Статус"."</h5>";
      echo "</td>";  
    echo "</tr>";


  foreach( $delayRets as $delayRet )
  {
    $inTime = $delayRet[8];
    $AcceptorID = $delayRet[12];
    $defInTime = $delayRet[9];
    $alloweDel = $delayRet[10];
    $delayVal = $delayRet[7];
    $status = $delayRet[6];

    $delayValStr = "$inTime > $defInTime + $alloweDel мин.<br> (".format_time_d_hhmmss_pure( $delayVal ).")"; 
    $delayValStr = "$inTime > $defInTime (".format_time_d_hhmmss_pure( $delayVal ).")"; 

    if ( $status == -1 )
    {
      $statusStr = "<h5 class=\"small2Red\">ОТКЛОНЕНО</h5>";
    }
    else if ( $status == 1 )
    {
      $statusStr = "<h5 class=\"small2Green\">ПРИНЯТО</h5>";
    }
    else
    {
      $statusStr = "<h5 class=\"small\">НА РАСМОТРЕНИИ</h5>";
    }

    $explaneDesk = $delayRet[3];

    $superUserName = get_superuser_name_by_id( $AcceptorID );
    $superUserReply = $delayRet[5];

    $addMsg1 = " <img title=\"Решение принял: $superUserName\" src=\"img/superuser.png\">";
    $addMsg2 = " <img title=\"Комментарий: $superUserReply\" src=\"img/delaySUExpl2.png\">";

    if ( $status == -1 OR $status == 1 )
    {
      $statusStr = $statusStr.$addMsg1.$addMsg2;
    }

    echo "<tr>";
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 135>";
        echo "<h5 class=\"small1\">$delayValStr</h5>";
      echo "</td>";  
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 240>";
        echo "<h5 class=\"small1\">$explaneDesk</h5>"."</font>";
      echo "</td>";  
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 150>";
        echo "$statusStr";
      echo "</td>";  
    echo "</tr>";
  }  
  echo "</table>";
}
?>