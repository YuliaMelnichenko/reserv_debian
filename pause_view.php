<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
include_once __DIR__ . "/funcs.php";
require_once __DIR__ . "/inc/notification_summary.php";
save_last_location( "pause_view.php" );
require_page_superuser();
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
</script>

<?php
$userID_ = (int)$_SESSION['ss_id'];

echo "<div class=\"notification-page-layout\">";

include_once __DIR__ . "/php_tori/connect.php";

mysqli_set_charset($link, "utf8");
$summary = get_pause_notification_summary($link, $userID_, get_current_datetime_in_timezone_str(1, 0));

if ($summary === false) {
  echo html_escape(database_error_message($link, __FILE__ . ':' . __LINE__));
  exit;
}

echo "<table class=\"notification-page-table\">";
  echo "<tr>";
    echo "<td class=\"notification-nav-cell\">";
      include_once __DIR__ . "/navigate.php";
    echo "</td>"; 

      echo "<td id=\"add_time_content_width\" class=\"notification-content-cell notification-content-cell-narrow\">";

        echo "<div id=\"addTimeHeader\">";
          echo "<h5 nowrap class=\"dark\"><br>/уведомления по приостановкам учета времени<br><br></h5>";
        echo "</div>";

    echo "<div class=\"notification-table-scroll notification-table-scroll-narrow\">";
    echo "<table id = \"pause_approvement_table_users\" class=\"add_time notification-summary-table\">";
    echo "<tr class=\"notification-table-head\">";
    echo "<td class=\"add_time notification-user-name-cell\">"."<h5 class=\"big\">Сотрудник</h5>"."</td>";
    echo "<td class=\"add_time notification-pause-count-cell\">"."<h5 class=\"big\">Всего</h5>"."</td>";
    echo "<td class=\"add_time notification-current-day-cell\">"."<h5 class=\"big\">За текущий день</h5>"."</td>";
    echo "<td class=\"add_time notification-pause-view-cell\">"."<h5 class=\"big\">Просмотреть</h5>"."</td>";
    echo "</tr>";

    $color = "#ddffff";
    $img = "go1.png";

    foreach ($summary['entries'] as $entry)
    {
        $userID = $entry['user_id'];
        $userName = $entry['user_name'];

        $mid = getMaskedUID( 32, $userID );
        $userUrl = "pause_view_user.php?mid=$mid";
        $uhref = "location.href='$userUrl'";

        $notificationCount = $entry['total_count'];
        $currentDayNotificationCount = $entry['current_day_count'];

        $rowClass = $color == "#ddffff" ? "notification-row-alt" : "notification-row";

        echo "<tr class=\"$rowClass\">";
        echo "<td class=\"add_time notification-user-name-cell\"><h5 class=\"middle\">" . html_escape($userName) . "</h5></td>";
        echo "<td class=\"add_time notification-pause-count-cell\">"."<h5 class=\"middle\">$notificationCount</h5>"."</td>";
        echo "<td class=\"add_time notification-current-day-cell\">"."<h5 class=\"middle\">$currentDayNotificationCount</h5>"."</td>";
        echo "<td class=\"add_time notification-pause-view-cell\">";
          echo "<button class=\"journal-view-button\" id=\"explBtn\" title=\"Просмотреть\" onclick=\"$uhref\"><img src=\"img/$img\"></button>";
        echo "</td>";
        echo "</tr>";

        if ( $color == "#ddffff" )
        { 
          $color = "#ffffff";
          $img = "go2.png";
        }
        else
        { 
          $color = "#ddffff";
          $img = "go1.png";
        }  
    }

    echo "</table>";
    echo "</div>";

          echo "</td>"; 
    echo "</tr>";
  echo "</table>";
echo "</div>";
?>

<script type="text/javascript" src="js/tory.js"></script> 
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
echo "</body>";
echo "</html>";  
?>
