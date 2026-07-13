<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID_ = $_SESSION['ss_id']; 

echo "<h5 class=\"big\">Уведомления по приостановкам учета времени</h5>";
echo "<table id=\"pause_approvement_table_users\" class=\"add_time notification-summary-table\">";
echo "<tr class=\"notification-table-head\">";
echo "<td class=\"add_time notification-user-name-cell\"><h5 class=\"big\">Сотрудник</h5></td>";
echo "<td class=\"add_time notification-pause-count-cell\"><h5 class=\"big\">Всего</h5></td>";
echo "<td class=\"add_time notification-current-day-cell\"><h5 class=\"big\">За текущий день</h5></td>";
echo "<td class=\"add_time notification-pause-view-cell\"><h5 class=\"big\">Просмотреть</h5></td>";
echo "</tr>";

$color = "#ddffff";
$img = "go1.png";

mysqli_set_charset($link, "utf8");
$query = db_query(
  $link,
  'SELECT DISTINCT USERID FROM GROUPS WHERE SUPERVISORID = ? AND TYPE = ? ORDER BY USERID',
  'ii',
  array((int)$userID_, 4)
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
    $currentDayNotificationCount = 0;
    get_pause_notif_counts( $userID, $notificationCount, $currentDayNotificationCount );

    $rowClass = $color == "#ddffff" ? "notification-row-alt" : "notification-row";

    echo "<tr class=\"$rowClass\">";
    echo "<td class=\"add_time notification-user-name-cell\"><h5 class=\"middle\">" . html_escape($userName) . "</h5></td>";
    echo "<td class=\"add_time notification-pause-count-cell\"><h5 class=\"middle\">$notificationCount</h5></td>";
    echo "<td class=\"add_time notification-current-day-cell\"><h5 class=\"middle\">$currentDayNotificationCount</h5></td>";
    echo "<td class=\"add_time notification-pause-view-cell\">";
      echo "<button id=\"explBtn\" class=\"journal-cell-icon-button\" title=\"Просмотреть\" onclick=\"show_pause_by_user('$userID');\"><img src=\"img/$img\" alt=\"\"></button>";
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
?>
