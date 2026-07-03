<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID_ = $_SESSION['ss_id']; 

$paramArr = get_dbsetup_param( 'add_time_journal_deep_day' );
  
$paramInt = (int)$paramArr[1];

$today = date("d-m-Y");
$dateForm = date("d.m.Y", strtotime("-$paramInt days"));

echo "<h5 class=\"big\"> Глубина просмотра журнала (180 дней): $dateForm - $today </h5>";
echo "<table class=\"add_time\" id = \"delay_approvement_table_users\" border=1>";
echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Сотрудник</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Всего</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Принятые</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Отклоненные</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Удаленные</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Новые</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Просмотреть</h5>"."</td>";
echo "</tr>";

$color = "#ddffff";
$img = "go1.png";

mysqli_set_charset($link, "utf8");
$query = mysqli_query($link, "SELECT DISTINCT USERID FROM GROUPS WHERE SUPERVISORID = '$userID_' AND TYPE = 3 "); 
if (!$query)
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else
{
  while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
  {  
    $userID = $row["USERID"];
    $userName = get_user_name_by_id($userID);

    $notificationCount = 0;
    $acceptedNotificationCount = 0;
    $refusedNotificationCount = 0;
    $deletedNotificationCount = 0;
    $newNotificationCount = 0;
    get_delay_notif_counts( $userID, $notificationCount, $acceptedNotificationCount, $refusedNotificationCount, $deletedNotificationCount, $newNotificationCount );

    $cellStype = "middle";
    if ( $newNotificationCount > 0 ){ $cellStype = "middleBlue1"; }

    echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
    echo "<td class=\"add_time\" nowrap valign=\"middle\" align=\"left\">"."<h5 class=\"middle\">$userName</h5>"."</td>";
    echo "<td class=\"add_time\" width = 60 valign=\"middle\" align=\"center\">"."<h5 class=\"middle\">$notificationCount</h5>"."</td>";
    echo "<td class=\"add_time\" width = 80 valign=\"middle\" align=\"center\">"."<h5 class=\"middle\">$acceptedNotificationCount</h5>"."</td>";
    echo "<td class=\"add_time\" width = 105 valign=\"middle\" align=\"center\">"."<h5 class=\"middle\">$refusedNotificationCount</h5>"."</td>";
    echo "<td class=\"add_time\" width = 90 valign=\"middle\" align=\"center\">"."<h5 class=\"middle\">$deletedNotificationCount</h5>"."</td>";
    echo "<td class=\"add_time\" width = 60 valign=\"middle\" align=\"center\">"."<h5 class=\"$cellStype\">$newNotificationCount</h5>"."</td>";
    echo "<td class=\"add_time\" width = 105 valign=\"middle\" align=\"center\">";
      echo "<button id = \"explBtn\" title = \"Просмотреть\" style=\"padding: 0px 0px 0px 0px; background-color:#ffffff; border:0px solid #888888;\" onclick=\"show_delays_by_user( '$userID' );\"><img src=\"img/$img\"></button>";
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