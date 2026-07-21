<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . "/../inc/notification_summary.php";

$userID_ = (int)$_SESSION['ss_id'];
$currentDate = get_current_datetime_in_timezone()[2];
$summary = get_delay_notification_summary($link, $userID_, $currentDate);

if ($summary === false) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

$depthDays = $summary['depth_days'];
$dateForm = $summary['range_start_label'];
$today = $summary['range_stop_label'];

echo "<h5 class=\"big\"> Глубина просмотра журнала ($depthDays дней): $dateForm - $today </h5>";
echo "<table class=\"add_time notification-summary-table\" id=\"delay_approvement_table_users\">";
echo "<tr class=\"notification-table-head\">";
echo "<td class=\"add_time notification-user-name-cell\"><h5 class=\"big\">Сотрудник</h5></td>";
echo "<td class=\"add_time notification-count-cell\"><h5 class=\"big\">Всего</h5></td>";
echo "<td class=\"add_time notification-accepted-cell\"><h5 class=\"big\">Принятые</h5></td>";
echo "<td class=\"add_time notification-refused-cell\"><h5 class=\"big\">Отклоненные</h5></td>";
echo "<td class=\"add_time notification-deleted-cell\"><h5 class=\"big\">Удаленные</h5></td>";
echo "<td class=\"add_time notification-count-cell\"><h5 class=\"big\">Новые</h5></td>";
echo "<td class=\"add_time notification-view-cell\"><h5 class=\"big\">Просмотреть</h5></td>";
echo "</tr>";

$color = "#ddffff";
$img = "go1.png";

foreach ($summary['entries'] as $entry)
{
    $userID = $entry['user_id'];
    $userName = $entry['user_name'];
    $notificationCount = $entry['total_count'];
    $acceptedNotificationCount = $entry['accepted_count'];
    $refusedNotificationCount = $entry['refused_count'];
    $deletedNotificationCount = $entry['deleted_count'];
    $newNotificationCount = $entry['new_count'];

    $cellStype = "middle";
    if ( $newNotificationCount > 0 ){ $cellStype = "middleBlue1"; }

    $rowClass = $color == "#ddffff" ? "notification-row-alt" : "notification-row";

    echo "<tr class=\"$rowClass\">";
    echo "<td class=\"add_time notification-user-name-cell\"><h5 class=\"middle\">" . html_escape($userName) . "</h5></td>";
    echo "<td class=\"add_time notification-count-cell\"><h5 class=\"middle\">$notificationCount</h5></td>";
    echo "<td class=\"add_time notification-accepted-cell\"><h5 class=\"middle\">$acceptedNotificationCount</h5></td>";
    echo "<td class=\"add_time notification-refused-cell\"><h5 class=\"middle\">$refusedNotificationCount</h5></td>";
    echo "<td class=\"add_time notification-deleted-cell\"><h5 class=\"middle\">$deletedNotificationCount</h5></td>";
    echo "<td class=\"add_time notification-count-cell\"><h5 class=\"$cellStype\">$newNotificationCount</h5></td>";
    echo "<td class=\"add_time notification-view-cell\">";
      echo "<button id=\"explBtn\" class=\"journal-cell-icon-button\" title=\"Просмотреть\" onclick=\"show_delays_by_user('$userID');\"><img src=\"img/$img\" alt=\"\"></button>";
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
