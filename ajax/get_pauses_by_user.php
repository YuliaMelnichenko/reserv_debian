<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$_SESSION['pause_page_mode'] = 2;

$userID = isset($_POST['user']) ? (int) $_POST['user'] : 0;

if ( $userID == -1 )
{ 
  $userID = isset($_SESSION['add_time_page_user_id'])
    ? (int) $_SESSION['add_time_page_user_id']
    : 0;
}

if ($userID <= 0) {
  deny_ajax_access(400, 'INVALID_USER');
}

require_ajax_self_or_superuser($userID);
$_SESSION['add_time_page_user_id'] = $userID;

$userName = get_user_name_by_id($userID);

echo "<table id=\"pause_approvement_table\" class=\"slim\" border=0>";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table class=\"slim\" border=0>";
        echo "<tr>";
          echo "<td class=\"nopadding\" valign=\"middle\" width=473 align=\"left\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
          echo "<td class=\"nopadding\" width=10 valign=\"middle\" align=\"right\">";
            echo "<button class=\"journal-back-button\" title=\"Назад\" onclick=\"pause_go_back();\"><h5>Назад</h5></button>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    echo "</td>";     
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"nopadding\" width=600 valign=\"middle\" align=\"left\">";

      echo "<table style=\"cellspacing: 0, padding: 0px; margin: 0;\" border=1>";
      echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";

      echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5>Дата</h5>"."</td>";
      echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5>Время</h5>"."</td>";
      echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5>Длительность</h5>"."</td>";
      echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5>Комментарий<br>работника</h5>"."</td>";
      echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5>С кем предварительно<br>согласовано</h5>"."</td>";
      echo "</tr>";
  
      $colorMode = 1;
      $color1 = "#ddffff";
      $color3 = "#ffffff";
      list($quarterStartDate, $quarterStopDate, $quarterStopExclusive) = get_current_quarter_date_range(false);

      $tempAddTimes = get_all_add_work_info_by_user( $userID, 1 );

      $addTimes = Array();

      foreach( $tempAddTimes as $tempAddTime )
      {
        if ( $tempAddTime[7] == 1 ) 
        {
          if (!is_time_defined($tempAddTime[0]) || !is_time_defined($tempAddTime[1])) {
            continue;
          }

          if (strtotime($tempAddTime[1]) <= strtotime($tempAddTime[0])) {
            continue;
          }

          if ($tempAddTime[0] < $quarterStartDate || $tempAddTime[0] >= $quarterStopExclusive) {
            continue;
          }

          $addTimes[] = $tempAddTime;
        }
      }

      foreach( $addTimes as $addTime )
      {
        $ta_start_date = date("Y-m-d", strtotime($addTime[0]));
        $ta_start_time = $addTime[0];
        $ta_stop_time = $addTime[1];
        $ta_duration = $addTime[6];
        $ta_description = $addTime[3];
        $ta_superuser = $addTime[5];

        $time_duration = format_time_d_hhmmss_pure( $ta_duration );
        $superUserName = get_superuser_name_by_id( $ta_superuser );

        if ( $colorMode == 0 )
        {
          $color = $color1;
          $colorMode = 1;
        }
        else
        {
          $color = $color3;
          $colorMode = 0;
        }

        echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
echo "<td nowrap class=\"nopadding_s\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($ta_start_date) . "</h5></td>";
echo "<td nowrap class=\"nopadding_s\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($ta_start_time . " - " . $ta_stop_time) . "</h5></td>";
        echo "<td nowrap class=\"nopadding_s\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($time_duration) . "</h5></td>";
echo "<td width=160 class=\"nopadding_s\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($ta_description) . "</h5></td>";
echo "<td width=140 class=\"nopadding_s\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
        echo "</tr>";
      }

      echo "</table>";
    echo "</td>";
  echo "</tr>";
echo "</table>";
?>
