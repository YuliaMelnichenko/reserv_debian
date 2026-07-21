<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . "/../inc/notification_summary.php";

$userID_ = (int)$_SESSION['ss_id'];
$summary = get_pause_notification_summary($link, $userID_, get_current_datetime_in_timezone_str(1, 0));

if ($summary === false) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

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

foreach ($summary['entries'] as $entry)
{
    $userID = $entry['user_id'];
    $userName = $entry['user_name'];
    $notificationCount = $entry['total_count'];
    $currentDayNotificationCount = $entry['current_day_count'];

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

echo "</table>";
?>
