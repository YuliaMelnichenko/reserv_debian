<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
include_once __DIR__ . "/funcs.php";
require_once __DIR__ . "/inc/pause_journal.php";
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
echo "<body class=\"app-page\">";
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

var timerId = setInterval(update_clock, 1000);
</script> 

<?php
echo "<div class=\"notification-page-layout\">";

include_once __DIR__ . "/php_tori/connect.php";

mysqli_set_charset($link, "utf8");

echo "<table class=\"notification-page-table\">";
  echo "<tr>";
    echo "<td class=\"notification-nav-cell\">";
      include_once __DIR__ . "/navigate.php";
    echo "</td>"; 

      echo "<td id=\"add_time_content_width\" class=\"notification-content-cell notification-content-cell-medium\">";

        echo "<div id=\"addTimeHeader\">";
          echo "<h5 class=\"dark\"><br>/уведомления по приостановкам учета времени<br><br></h5>";
        echo "</div>";

      $backUrl = "pause_view.php";
      $journal = get_pause_journal_context($link, $userID, get_current_datetime_in_timezone_str(1, 0));

      if ($journal === false) {
        echo "<h5>" . html_escape(database_error_message($link, __FILE__ . ':' . __LINE__)) . "</h5>";
        exit;
      }

      if ($journal === null) {
        header('Location: pause_view.php');
        exit;
      }

      $userName = $journal['user_name'];
      $addTimes = $journal['entries'];

      if ( count( $addTimes ) == 0 )
      {

echo "<table id=\"pause_approvement_table\" class=\"notification-detail-header-table\">";
  echo "<tr>";
     echo "<td class=\"notification-detail-title-cell notification-detail-title-medium\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
     echo "<td class=\"notification-detail-back-cell\">";
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

echo "<table id=\"pause_approvement_table\" class=\"notification-detail-header-table\">";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table class=\"notification-detail-header-table\">";
        echo "<tr>";
          echo "<td class=\"notification-detail-title-cell notification-detail-title-medium\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
          echo "<td class=\"notification-detail-back-cell\">";
            echo "<button class=\"journal-back-button\" title=\"Назад\" onclick=\"location.href='$backUrl';\"><h5>Назад</h5></button>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    echo "</td>";     
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"nopadding notification-detail-body-cell\">";

      echo "<div class=\"notification-table-scroll notification-table-scroll-medium\">";
      echo "<table class=\"add_time notification-detail-table pause-detail-table\">";
      echo "<tr class=\"notification-detail-head\">";

      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Начало<br>(дата, время)</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Окончание<br>(дата, время)</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Длительность</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Комментарий<br>работника</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>С кем предварительно<br>согласовано</h5>"."</td>";
      echo "</tr>";
  
      $colorMode = 1;
      $color1 = "#ddffff";
      $color3 = "#ffffff";

      foreach( $addTimes as $addTime )
      {

        $ta_start_dt = $addTime['start_datetime'];
        $ta_stop_dt = $addTime['stop_datetime'];
        $ta_duration = $addTime['duration'];
        $ta_description = $addTime['employee_comment'];
        $superUserName = $addTime['supervisor_name'];

        $time_duration = format_time_d_hhmmss_pure( $ta_duration );

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

        $rowClass = $color == $color1 ? "notification-detail-row-alt" : "notification-detail-row";

        echo "<tr class=\"$rowClass\">";
        echo "<td class=\"add_time notification-detail-date-cell\"><h5 class=\"small\">" . html_escape($ta_start_dt) . "</h5></td>";
        echo "<td class=\"add_time notification-detail-date-cell\"><h5 class=\"small\">" . html_escape($ta_stop_dt) . "</h5></td>";
        echo "<td class=\"add_time notification-detail-duration-cell\"><h5 class=\"small\">".$time_duration."</h5></td>";
        echo "<td class=\"add_time notification-detail-comment-cell\"><h5 class=\"small\">" . html_escape($ta_description) . "</h5></td>";
        echo "<td class=\"add_time notification-detail-supervisor-cell\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
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
