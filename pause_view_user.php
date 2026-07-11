<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
include_once __DIR__ . "/funcs.php";
save_last_location( "pause_view.php" );
$mid = (string) ($_GET['mid'] ?? '');

if ($mid === '') {
  header('Location: pause_view.php');
  exit;
}

$resArr = extractUidFromMaskedUID($mid);
$uidValid = (int) $resArr[0];
$userID = (int) $resArr[1];

if ($uidValid === 0 || $userID <= 0) {
  header('Location: pause_view.php');
  exit;
}

require_page_supervisor_for_user($userID, 4);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body bgcolor=\"#ffffff\" >";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" charset="utf-8"> 

function update_clock()
{
  $.post('ajax/get_current_day_time.php', RetSWT);                           
  function RetSWT(dat) 
  {
    if ( document.getElementById('dateTimeFieldNav') )
    {
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId=setInterval( "update_clock()", 1000 );
</script> 

<?php
echo "<div align=\"left\">";

include_once __DIR__ . "/php_tori/connect.php";

mysqli_set_charset($link, "utf8");

echo "<table border=0>";
  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";
      include_once __DIR__ . "/navigate.php";
    echo "</td>"; 
      $wholeWidth = 688;

      echo "<td id=\"add_time_content_width\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

        echo "<div id=\"addTimeHeader\">";
          echo "<h5 class=\"dark\"><br>/уведомления по приостановкам учета времени<br><br></h5>";
        echo "</div>";

      $userName = get_user_name_by_id($userID);
      $backUrl = "pause_view.php";

      $addTimeInfo = get_all_add_work_info_by_user( $userID, 1 );

      if ( count( $addTimeInfo ) == 0 )
      {

echo "<table id=\"pause_approvement_table\" border=0>";
  echo "<tr>";
     echo "<td valign=\"middle\" width=604 align=\"left\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
     echo "<td width=10 valign=\"middle\" align=\"right\">";
       echo "<button class=\"journal-back-button\" title=\"Назад\" onclick=\"location.href='$backUrl';\"><h5>Назад</h5></button>";
     echo "</td>";
  echo "</tr>";
echo "</table>";
        echo "<h5><br>Нет сведений!</h5>";
        echo "</td>";               
        echo "<tr>";
        echo "<table>";
        exit;
      }

echo "<table id=\"pause_approvement_table\" border=0>";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table border=0>";
        echo "<tr>";
          echo "<td valign=\"middle\" width=604 align=\"left\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
          echo "<td width=10 valign=\"middle\" align=\"right\">";
            echo "<button class=\"journal-back-button\" title=\"Назад\" onclick=\"location.href='$backUrl';\"><h5>Назад</h5></button>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    echo "</td>";     
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"nopadding\" valign=\"middle\" align=\"left\">";

      echo "<div class=\"notification-table-scroll notification-table-scroll-medium\">";
      echo "<table border=1>";
      echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";

      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Начало<br>(дата, время)</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Окончание<br>(дата, время)</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Длительность</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Комментарий<br>работника</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>С кем предварительно<br>согласовано</h5>"."</td>";
      echo "</tr>";
  
      $colorMode = 1;
      $color1 = "#ddffff";
      $color3 = "#ffffff";

      $tempAddTimes = get_all_add_work_info_by_user( $userID, 1 );

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

        $ta_start_dt = $addTime[0];
        $ta_stop_dt = $addTime[1];
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
        echo "<td width=100 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($ta_start_dt) . "</h5></td>";
        echo "<td width=100 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($ta_stop_dt) . "</h5></td>";
        echo "<td width=85 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">".$time_duration."</h5></td>";
        echo "<td width=140 class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($ta_description) . "</h5></td>";
        echo "<td width=200 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
        echo "</tr>";
      }

      echo "</table>";
      echo "</div>";
    echo "</td>";
  echo "</tr>";
echo "</table>";
?>

<script type="text/javascript" src="js/tory.js"></script> 

<?php
echo "</body>";
echo "</html>";  
?>
