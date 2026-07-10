<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID_ = $_SESSION['ss_id']; 
$currentDate = date('Y-m-d');

include_once __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

$newID = 0;

$user_defaultStartTime = $_SESSION['ss_defaultStartTime'];
$user_allowedDelay = $_SESSION['ss_allowedDelay'];

// $addRets = get_add_work_info_by_user_and_day( $userID_, $currentDate );

$found = 0;
$status = 0;
$suid = -1;

// foreach( $addRets as $addRet )
// {
//   if ( $addRet[7] == 1 ){ continue; }
//   if ( $addRet[4] == -2 ){ continue; }
//   $found = 1;
// }

if ( $found == 0 )
{
  echo "";
}
else
{
  echo "<br><h5 class=\"big\">Работа вне офиса и приостановки учета времени за текущий день</h5>";
  echo "<br><table class=\"slim\" border=1>";
    echo "<tr bgcolor=\"#DDDDDD\">";
      echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\">";
        echo "<h5 class=\"middle1\">Длительность"."</h5>";
      echo "</td>";  
      echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\">";
        echo "<h5 class=\"middle1\">Основание"."</h5>";
      echo "</td>";  
      echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\">";
        echo "<h5 class=\"middle1\">Комментарий работника"."</h5>";
      echo "</td>";  
      echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\">";
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
    $pauseMode = $addRet[7]; 
    $suDesc = $addRet[10]; 

    if ( $pauseMode == 1 ){ continue; }
    if ( $approved == 99 OR $approved == 100 OR $approved == 101 ){ continue; }

    $timeDurationStr = format_time_d_hhmm_pure( $timeDuration );

    if ( $reason == 1 ){ $reasonStr = "Доставка комплектующих,<br>оборудования и т.д."; }
    else if ( $reason == 2 ){ $reasonStr = "Работа не в офисе"; }
    else if ( $reason == 3 ){ $reasonStr = "Работа на дому"; }
    else if ( $reason == 4 ){ $reasonStr = "Прочее"; }
    else if ( $reason == 5 ){ $reasonStr = "Командировка"; }
    else if ( $reason == 7 ){ $reasonStr = "Болезнь"; }
    else if ( $reason == 4 OR $reason == 8 ){ $reasonStr = "Прочее"; }

    $superUserName = get_sv_name_by_userid( $userID_ );
    $addMsg1 = " <img title=\"Решение принял: $superUserName\" src=\"img/superuser.png\">";

    $bgcolor = "";    

    if ( $approved == 0 )
    { 
      $approvedStr = journal_status_label("на рассмотрении", "middle");
    }
    else if ( $approved == -1 )
    { 
      $approvedStr = "отклонено"; $bgcolor = "#FFAAAA"; 
      $ta_approved_str_add1 = " <img title=\"решение принял: $superUserName\" src=\"img/superuserBad.png\">";
      $ta_approved_str_add2 = " <img title=\"комментарий: $suDesc\" src=\"img/delaySUExpl2Bad.png\">";
      $content1 = "<table class=\"slim\" border=0>";
        $content1 .= "<tr>";
          $content1 .= "<td class=\"nopadding_s\" width=\"60\" align=\"left\" >";
            $content1 .= journal_status_label($approvedStr, "middle");
          $content1 .= "</td>"; 
          $content1 .= "<td class=\"nopadding\" width=\"40\" align=\"right\" >";
            $content1 .= "<h5 class=\"middle\">$ta_approved_str_add1$ta_approved_str_add2</h5>";
          $content1 .= "</td>";
        $content1 .= "</tr>";
      $content1 .= "</table>";
      $approvedStr = $content1;
    }
    else if ( $approved == 1 )
    {
      $approvedStr = "принято"; $bgcolor = "#AAFFAA"; 
      $ta_approved_str_add1 = " <img title=\"решение принял: $superUserName\" src=\"img/superuserGood.png\">";
      $ta_approved_str_add2 = " <img title=\"комментарий: $suDesc\" src=\"img/delaySUExpl2Good.png\">";
      $content1 = "<table class=\"slim\" border=0>";
        $content1 .= "<tr>";
          $content1 .= "<td class=\"nopadding_s\" width=\"60\" align=\"left\" >";
            $content1 .= journal_status_label($approvedStr, "middle");
          $content1 .= "</td>"; 
          $content1 .= "<td class=\"nopadding\" width=\"40\" align=\"right\" >";
            $content1 .= "<h5 class=\"middle\">$ta_approved_str_add1$ta_approved_str_add2</h5>";
          $content1 .= "</td>";
        $content1 .= "</tr>";
      $content1 .= "</table>";
      $approvedStr = $content1;
    }   

    echo "<tr>";
      echo "<td nowrap class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
        echo "<h5 class=\"small1\">$startTime - $stopTime<br>= $timeDurationStr"."</h5>";
      echo "</td>";  
      echo "<td width = 120 class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
echo "<h5 class=\"small1\">" . html_escape($reasonStr) . "</h5>";
      echo "</td>";  
      echo "<td width = 148 class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
echo "<h5 class=\"small1\">" . html_escape($description) . "</h5>";
      echo "</td>";  
      echo "<td nowrap width = 120 class=\"nopadding_s\" bgcolor=\"$bgcolor\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\">";
        echo $approvedStr;
      echo "</td>";  
    echo "</tr>";
  }

  foreach( $addRets as $addRet )
  {
    $startTime = $addRet[0];
    $stopTime = $addRet[1];
    $reason = $addRet[2];
    $description = $addRet[3];
    $approved = $addRet[4];
    $suid = $addRet[5];
    $timeDuration = $addRet[6];
    $pauseMode = $addRet[7]; 

    if ( $pauseMode == 0 ){ continue; }

    $timeDurationStr = format_time_d_hhmmss_pure( $timeDuration );

    $reasonStr = "Приостановка учета времени";

    $superUserName = get_sv_name_by_userid( $userID_ );
    $addMsg1 = " <img title=\"Решение принял: $superUserName\" src=\"img/superuser.png\">";

    $bgcolor = "";

    $approvedStr = "Утверждению не подлежит";     

    echo "<tr bgcolor=\"#EEEEEE\">";
      echo "<td nowrap class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
        echo "<h5 class=\"small1\">($timeDurationStr)<br>[$startTime-$stopTime]"."</h5>";
      echo "</td>";  
      echo "<td width = 120 class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
echo "<h5 class=\"small\">" . html_escape($reasonStr) . "</h5>";
      echo "</td>";  
      echo "<td width = 120 class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
echo "<h5 class=\"small1\">" . html_escape($description) . "</h5>";
      echo "</td>";  
      echo "<td nowrap width = 120 class=\"nopadding_s\" valign=\"middle\" align=\"center\">";
        echo journal_status_label($approvedStr, "middle");
      echo "</td>";  
    echo "</tr>";
  }

  echo "</table>";
}
?>
