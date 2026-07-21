<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID_ = (int)$_SESSION['ss_id'];
$user_defaultStartTime = $_SESSION['ss_defaultStartTime'];
$user_allowedDelay = (int)$_SESSION['ss_allowedDelay'];

echo "<div class=\"notification-table-scroll notification-table-scroll-full\">";

echo "<table class=\"add_time\" cellpadding=\"0\" cellspacing=\"0\" border=1>";
echo "<tr bgcolor=\"#DDDDDD\" bordercolor=\"#888888\">";

echo "<td valign=\"middle\" align=\"center\">"."<h5>Дата</h5>"."</td>";
echo "<td valign=\"middle\" align=\"center\">"."<h5>Время прихода</h5>"."</td>";
echo "<td valign=\"middle\" align=\"center\">"."<h5>Время начала рабочего дня<br> + допустимое опоздание</h5>"."</td>";
echo "<td valign=\"middle\" align=\"center\">"."<h5>Длительность<br>опоздания</h5>"."</td>";
echo "<td valign=\"middle\" align=\"center\">"."<h5>Комментарий<br>работника</h5>"."</td>";
echo "<td valign=\"middle\" align=\"center\">"."<h5>С кем предварительно<br>огласовано</h5>"."</td>";
echo "<td valign=\"middle\" align=\"center\">"."<h5>Лицо, принявшее<br>решение</h5>"."</td>";
echo "<td valign=\"middle\" align=\"center\">"."<h5>Комментарий лица,<br>принявшего решение</h5>"."</td>";
echo "<td valign=\"middle\" align=\"center\">"."<h5>Статус</h5>"."</td>";
echo "<td valign=\"middle\" align=\"center\">"."<h5>Управление</h5>"."</td>";
echo "</tr>";

$colorMode = 1;
$color1 = "#ddffff";
$color3 = "#ffffff";

$delays = get_all_delay_info_by_user( $userID_, $user_defaultStartTime, $user_allowedDelay );

foreach( $delays as $delay )
{
  $delID = (int)$delay[0];
  $delDate = $delay[11];
  $delInTime = $delay[8];
  $delDefInTime = $delay[9];
  $delAllowedDelay = $delay[10];
  $delDefInTimeWithDelay = $datetime = date("H:i:s", strtotime($delDefInTime."+ $delAllowedDelay minute"));
  $delInTime = substr( $delInTime, 11, 8 );
  $delDuration = $delay[7];
  $delComment = $delay[3];
  $delStatus = $delay[6];
  $delSUser = $delay[1];
  $delAcceptorReply = $delay[5];
  $delAcceptorID = $delay[12];

  $delDurationStr = format_time_d_hhmmss_pure($delDuration);

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

  if ( $delSUser != -1 )
  {
    $agreedColor = "#AAFFAA";
  }
  else
  {
    $agreedColor = "#FFAAAA";
  }

  if ( $delSUser == -1 )
  {
    $superUserName = "Ни с кем!";
  }
  else
  {
    $superUserName = get_superuser_name_by_id( $delSUser );
  }

  $acceptorName = get_superuser_name_by_id( $delAcceptorID );

  if ( $delStatus == 0 )
  {
    $approvedStr = journal_status_label("на рассмотрении");
    $cellColor = "";
    $buttonAdd1 = "";
    $buttonAdd2 = "onclick=\"delay_set('$delID', '$userID_');\"";
    $buttonAdd3 = "";
  }
  else if ( $delStatus == -1 )
  {
    $approvedStr = journal_status_label("отклонено");
    $cellColor = "#FFAAAA";
    $buttonAdd1 = "disabled";
    $buttonAdd2 = "";
    $buttonAdd3 = "title=\"запись уже заквитирована. Изменение невозможно\"";
  }
  else if ( $delStatus == 1 )
  {
    $approvedStr = journal_status_label("принято");
    $cellColor = "#AAFFAA";
    $buttonAdd1 = "disabled";
    $buttonAdd2 = "";
    $buttonAdd3 = "title=\"запись уже заквитирована. Изменение невозможно\"";
  }

  echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
  echo "<td width=70 valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($delDate) . "</h5></td>";
  echo "<td width=105 valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($delInTime) . "</h5></td>";
  echo "<td width=185 valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape("$delDefInTime >> $delDefInTimeWithDelay (+ $delAllowedDelay мин.)") . "</h5></td>";
  echo "<td width=95 valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($delDurationStr) . "</h5></td>";
  echo "<td width=140 valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($delComment) . "</h5></td>";
echo "<td width=200 bgcolor=\"$agreedColor\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
echo "<td width=200 valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($acceptorName) . "</h5></td>";
  echo "<td width=120 valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($delAcceptorReply) . "</h5></td>";
  echo "<td width=130 bgcolor=\"$cellColor\" valign=\"middle\" align=\"center\">$approvedStr</td>";
  echo "<td width=160 valign=\"middle\" align=\"center\">";
    echo "<button class=\"journal-action-button journal-action-button-delay\" $buttonAdd1 $buttonAdd2 $buttonAdd3 name=\"nextBtn\">Внести объяснение</button>";
  echo "</td>";
  echo "</tr>";
}
echo "</table>";
echo "</div>";
?>
