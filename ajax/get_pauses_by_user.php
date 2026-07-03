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

$userID = $_POST['user'];

if ( $userID != -1 )
{ 
  $_SESSION['add_time_page_user_id'] = $userID;
}
else
{ 
  $userID = $_SESSION['add_time_page_user_id'];
}

$userName = get_user_name_by_id($userID);

echo "<table id=\"pause_approvement_table\" class=\"slim\" border=0>";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table class=\"slim\" border=0>";
        echo "<tr>";
          echo "<td class=\"nopadding\" valign=\"middle\" width=473 align=\"left\">"."<h5 class=\"bigbig17\">$userName</h5>"."</td>";
          echo "<td class=\"nopadding\" width=10 valign=\"middle\" align=\"right\">";
            echo "<button title = \"Назад\" style=\"padding: 5px 5px 5px 5px; width:73px; height:25px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"pause_go_back();\"><h5>Назад</h5></button>";
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
      $color2 = "#ddeedd";
      $color3 = "#ffffff";

      $tempAddTimes = get_all_add_work_info_by_user( $userID, 0 );

      $addTimes = Array();

      foreach( $tempAddTimes as $tempAddTime )
      {
        if ( $tempAddTime[7] == 1 ) 
        {
          $addTimes[] = $tempAddTime;
        }
      }

      foreach( $addTimes as $addTime )
      {
        $ta_id = $addTime[8];
        $ta_start_date = $addTime[9];
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
        echo "<td nowrap class=\"nopadding_s\" valign=\"middle\" align=\"center\"><h5 class=\"small\">".$time_duration."</h5></td>";
echo "<td width=160 class=\"nopadding_s\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($ta_description) . "</h5></td>";
echo "<td width=140 class=\"nopadding_s\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
        echo "</tr>";
      }

      echo "</table>";
    echo "</td>";
  echo "</tr>";
echo "</table>";
?>
