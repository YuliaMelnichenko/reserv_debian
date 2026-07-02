<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";

$userID_ = $_SESSION['ss_id']; 
$currentDate = get_current_datetime_in_timezone_str( 1, 0 );

include_once __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

$newID = 0;

$user_defaultStartTime = $_SESSION['ss_defaultStartTime'];
$user_allowedDelay = $_SESSION['ss_allowedDelay'];

$delayRets = get_delay_info_by_user_and_day( $userID_, $currentDate, $user_defaultStartTime, $user_allowedDelay );

$found = 0;
$status = 0;
$suid = -1;

if ( count( $delayRets ) > 0 )
{
  $found = 1;
}

if ( $found == 0 )
{
  echo "";
}
else
{
  echo "<br><h5 class=\"big\">Опоздание за текущий день</h5>";
  echo "<br><table class=\"hor_bor\" border=1>";
    echo "<tr>";
      echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
        echo "<h5 class=\"middle1\">Длительность"."</h5>";
      echo "</td>";  
      echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\">";
        echo "<h5 class=\"middle1\">Объяснение работника"."</h5>";
      echo "</td>";  
      echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\">";
        echo "<h5 class=\"middle1\">Статус"."</h5>";
      echo "</td>";  
    echo "</tr>";

    foreach( $delayRets as $delayRet )
    {
      $suid = $delayRet[1];
      $status = $delayRet[6];
      $explaneDesk = $delayRet[3];

      $delayVal = $delayRet[7];
      $inTime = $delayRet[9];


      $allowedInTimeStr = substr( $user_defaultStartTime, 0, 6 ).(string)($user_allowedDelay);

      $delayValStr = "$inTime > $allowedInTimeStr<br>= ".format_time_d_hhmmss_pure( $delayVal ); 

      $superUserName = get_sv_name_by_userid( $userID_ );
      $superUserReply = $delayRet[5];

      if ( $status == 0 )
      { 
      $statusStr = "<h5 class=\"middle\">на рассмотрении</h5>"; 
      }
        else if ( $status == -1 )
      { 
        $approvedStr = "отклонено"; $bgcolor = "#FFAAAA"; 
        $ta_approved_str_add1 = "<img title=\"решение принял: $superUserName\" src=\"img/superuserBad.png\">";
        $ta_approved_str_add2 = " <img title=\"комментарий: $superUserReply\" src=\"img/delaySUExpl2Bad.png\">";
        $content1 = "<table class=\"slim\" border=0>";
          $content1 .= "<tr>";
            $content1 .= "<td class=\"nopadding_s\" width=\"60\" align=\"left\" >";
              $content1 .= "<h5 class=\"middle\">$approvedStr</h5>";
            $content1 .= "</td>"; 
            $content1 .= "<td class=\"nopadding\" width=\"40\" align=\"right\" >";
              $content1 .= "<h5 class=\"middle\">$ta_approved_str_add1$ta_approved_str_add2</h5>";
            $content1 .= "</td>";
          $content1 .= "</tr>";
        $content1 .= "</table>";
        $statusStr = $content1;
      }
      else if ( $status == 1 )
      {
        $approvedStr = "принято"; $bgcolor = "#AAFFAA"; 
        $ta_approved_str_add1 = "<img title=\"решение принял: $superUserName\" src=\"img/superuserGood.png\">";
        $ta_approved_str_add2 = " <img title=\"комментарий: $superUserReply\" src=\"img/delaySUExpl2Good.png\">";
        $content1 = "<table class=\"slim\" border=0>";
          $content1 .= "<tr>";
            $content1 .= "<td class=\"nopadding_s\" width=\"60\" align=\"left\" >";
              $content1 .= "<h5 class=\"middle\">$approvedStr</h5>";
            $content1 .= "</td>"; 
            $content1 .= "<td class=\"nopadding\" width=\"40\" align=\"right\" >";
              $content1 .= "<h5 class=\"middle\">$ta_approved_str_add1$ta_approved_str_add2</h5>";
            $content1 .= "</td>";
          $content1 .= "</tr>";
        $content1 .= "</table>";
        $statusStr = $content1;
      }  

      echo "<tr>";
        echo "<td nowrap class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
          echo "<h5 class=\"small1\">$delayValStr</h5>";
        echo "</td>";  
        echo "<td  width = 263 bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
          echo "<h5 class=\"small1\">$explaneDesk</h5>"."</font>";
        echo "</td>";  
        echo "<td nowrap  width = 120 class=\"nopadding_s\" bgcolor=\"$bgcolor\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\">";
          echo "$statusStr";
        echo "</td>";  
      echo "</tr>";
    }
  echo "</table>";
}
?>                                                                   