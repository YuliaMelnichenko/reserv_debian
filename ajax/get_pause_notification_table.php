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
echo "<table id = \"pause_approvement_table_users\" class=\"slim\" border=1>";
echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";
echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Сотрудник</h5>"."</td>";
echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Всего</h5>"."</td>";
echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">За текущий день</h5>"."</td>";
echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Просмотреть</h5>"."</td>";
echo "</tr>";

$color = "#ddffff";
$img = "go1.png";

mysqli_set_charset($link, "utf8");
$query = mysqli_query($link, "SELECT DISTINCT USERID FROM GROUPS WHERE SUPERVISORID = '$userID_' AND TYPE = 4 "); 
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
    $currentDayNotificationCount = 0;
    get_pause_notif_counts( $userID, $notificationCount, $currentDayNotificationCount );

    $cellStype = "middle";
    if ( $notificationCount > 0 ){ $cellStype = "middleBlue1"; }

    echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
    echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"left\">"."<h5 class=\"middle\">$userName</h5>"."</td>";
    echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"middle\">$notificationCount</h5>"."</td>";
    echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"middle\">$currentDayNotificationCount</h5>"."</td>";
    echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">";
      echo "<button id = \"explBtn\" class=\"journal-cell-icon-button\" title = \"Просмотреть\" onclick=\"show_pause_by_user( '$userID' );\"><img src=\"img/$img\"></button>";
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
