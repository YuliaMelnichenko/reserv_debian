<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID_ = $_SESSION['ss_id']; 

$paramArr = get_dbsetup_param( 'add_time_journal_deep_day' );
  
$paramInt = (int)$paramArr[1];

$today = date("d-m-Y");
$dateForm = date("d.m.Y", strtotime("-$paramInt days"));

echo "<h5 class=\"big\"> Глубина просмотра журнала (180 дней): $dateForm - $today </h5>";
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

mysqli_set_charset($link, "utf8");
$query = db_query(
  $link,
  'SELECT DISTINCT USERID FROM GROUPS WHERE SUPERVISORID = ? AND TYPE = ? ORDER BY USERID',
  'ii',
  array((int)$userID_, 3)
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
}

echo "</table>";
?>
