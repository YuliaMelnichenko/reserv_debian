<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
include_once __DIR__ . "/funcs.php";
save_last_location( "delay_approvement.php" );
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

echo "<input id=\"recIDTempVal\" type=\"hidden\" value=\"\">";
echo "<input id=\"acceptTempVal\" type=\"hidden\" value=\"\">";
echo "<input id=\"penIDTempVal\" type=\"hidden\" value=\"\">";
echo "<input id=\"penDateTempVal\" type=\"hidden\" value=\"\">";
echo "<input id=\"penUserIDTempVal\" type=\"hidden\" value=\"\">";

echo "<table class=\"notification-page-table\">";
  echo "<tr>";
    echo "<td class=\"notification-nav-cell\">";
      include_once __DIR__ . "/navigate.php";
    echo "</td>";

    echo "<td class=\"notification-content-cell notification-content-cell-wide\">";

    echo "<div id=\"delayHeader\">";
      echo "<h5 class=\"dark\"><br>/уведомления по опозданиям<br><br></h5>";
    echo "</div>";

echo "<div class=\"notification-table-scroll notification-table-scroll-wide\">";
echo "<table class=\"add_time notification-summary-table\" id = \"delay_approvement_table_users\">";
echo "<tr class=\"notification-table-head\">";
echo "<td class=\"add_time notification-user-name-cell\">"."<h5 class=\"big\">Сотрудник</h5>"."</td>";
echo "<td class=\"add_time notification-count-cell\">"."<h5 class=\"big\">Всего</h5>"."</td>";
echo "<td class=\"add_time notification-accepted-cell\">"."<h5 class=\"big\">Принятые</h5>"."</td>";
echo "<td class=\"add_time notification-refused-cell\">"."<h5 class=\"big\">Отклоненные</h5>"."</td>";
echo "<td class=\"add_time notification-deleted-cell\">"."<h5 class=\"big\">Удаленные</h5>"."</td>";
echo "<td class=\"add_time notification-count-cell\">"."<h5 class=\"big\">Новые</h5>"."</td>";
echo "<td class=\"add_time notification-view-cell\">"."<h5 class=\"big\">Просмотреть</h5>"."</td>";
echo "</tr>";

$color = "#ddffff";
$img = "go1.png";

mysqli_set_charset($link, "utf8");
$query = db_query(
  $link,
  'SELECT DISTINCT USERID FROM GROUPS WHERE SUPERVISORID = ? AND TYPE = ? ORDER BY USERID',
  'ii',
  array($userID_, 3)
);
if (!$query)
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else
{
  while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
  {
    $userID = (int)$row["USERID"];
    $userName = get_user_name_by_id($userID);

    $notificationCount = 0;
    $acceptedNotificationCount = 0;
    $refusedNotificationCount = 0;
    $deletedNotificationCount = 0;
    $newNotificationCount = 0;
    get_delay_notif_counts( $userID, $notificationCount, $acceptedNotificationCount, $refusedNotificationCount, $deletedNotificationCount, $newNotificationCount );

    $muid = getMaskedUID( 32, $userID );
    $userUrl = "delay_approvement_user.php?mid=$muid";
    $uhref = "location.href='$userUrl'";

    $cellStype = "middle";
    if ( $newNotificationCount > 0 ){ $cellStype = "middleBlue1"; }

    $rowClass = $color == "#ddffff" ? "notification-row-alt" : "notification-row";

    echo "<tr class=\"$rowClass\">";
    echo "<td class=\"add_time notification-user-name-cell\"><h5 class=\"middle\">" . html_escape($userName) . "</h5></td>";
    echo "<td class=\"add_time notification-count-cell\">"."<h5 class=\"middle\">$notificationCount</h5>"."</td>";
    echo "<td class=\"add_time notification-accepted-cell\">"."<h5 class=\"middle\">$acceptedNotificationCount</h5>"."</td>";
    echo "<td class=\"add_time notification-refused-cell\">"."<h5 class=\"middle\">$refusedNotificationCount</h5>"."</td>";
    echo "<td class=\"add_time notification-deleted-cell\">"."<h5 class=\"middle\">$deletedNotificationCount</h5>"."</td>";
    echo "<td class=\"add_time notification-count-cell\">"."<h5 class=\"$cellStype\">$newNotificationCount</h5>"."</td>";
    echo "<td class=\"add_time notification-view-cell\">";
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

var timerId = setInterval(update_clock, 10000);
</script>

<?php
echo "</body>";
echo "</html>";
?>
