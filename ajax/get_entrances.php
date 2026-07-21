<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_superuser();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID_ = (int)$_SESSION['ss_id'];

$currentTime = date("H:i:s");

echo "<table id = \"entrance_approvement_table_users\" class=\"slim\" border=1>";
echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";
echo "<td width=130 class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Сотрудник</h5>"."</td>";
echo "<td width=100 class=\"nopadding_s\" valign=\"middle\" align=\"left\">"."<h5 class=\"big\">Зарегистрированное<br>время прихода</h5>"."</td>";
echo "<td width=130 class=\"nopadding_s\" valign=\"middle\" align=\"left\">"."<h5 class=\"big\">Новое значение<br>времени прихода</h5>"."</td>";
echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"left\">"."<h5 class=\"big\">Управление</h5>"."</td>";
echo "</tr>";

$colorMode = 1;
$color1 = "#ddffff";
$color2 = "#ddeedd";
$color3 = "#ffffff";

$userInTimes = get_users_current_day_in_time_by_superuser( $userID_ );

$rowID = 0;

foreach( $userInTimes as $userInTime )
{
  $retUserID = (int)$userInTime[0];
  $retUserInTime = $userInTime[1];
  $adjMode = $userInTime[2];

  if ( $adjMode == -1 ) continue;  

  $userName = get_user_name_by_id( $retUserID );

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

  $rowID = "newInTime".$rowID;
 
  echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
  echo "<td nowrap width=130 class=\"nopadding_s\" valign=\"middle\" align=\"left\"><h5 class=\"middle\">" . html_escape($userName) . "</h5></td>";
  echo "<td width=100 class=\"nopadding_s\" valign=\"middle\" align=\"center\"><h5 class=\"big\">" . html_escape($retUserInTime) . "</h5></td>";
  echo "<td width=130 class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">";
    echo "<input id=\"" . html_escape($rowID) . "\" align=\"center\" style=\"width:130px;\" type=\"text\" value=\"" . html_escape($currentTime) . "\">";
  echo "</td>";
  echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">";
    echo "<button id = \"explBtn\" title = \"Просмотреть\" style=\"padding: 0px 0px 0px 0px; width:90px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"set_new_entrance_time($retUserID, " . html_escape(js_encode($retUserInTime)) . ", document.getElementById(" . html_escape(js_encode($rowID)) . ").value);\">Задать</button>";
  echo "</td>";
  echo "</tr>";  
}
echo "</table>";
?>
