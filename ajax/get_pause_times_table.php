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
$currentDate = get_current_datetime_in_timezone_str( 1, 0 );
$paramArr = get_dbsetup_param( 'pause_journal_deep_day' );
$paramInt = (int)$paramArr[1];
$today = date("d-m-Y");
$dateForm = date("d.m.Y", strtotime("-$paramInt days"));
$startExpr = add_time_datetime_sql('a.START_DT', 'a.STARTDATE', 'a.STARTTIME');
$stopExpr = add_time_datetime_sql('a.STOP_DT', 'a.STARTDATE', 'a.STOPTIME');

echo "<h5 class=\"big\"> Глубина просмотра журнала (180 дней): $dateForm - $today </h5>";
echo "<div class=\"notification-table-scroll notification-table-scroll-medium\">";
echo "<table class=\"add_time\" border=1>";
echo "<tr bgcolor=\"#DDDDDD\" bordercolor=\"#888888\">";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Начало<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Окончание<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Длительность</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Комментарий<br></h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>С кем предварительно<br>согласовано</h5>"."</td>";
echo "</tr>";
  
$colorMode = 1;
$color1 = "#ddffff";
$color2 = "#ddeedd";
$color3 = "#ffffff";

mysqli_set_charset($link, "utf8");

$query = mysqli_query($link, "SELECT a.*,
                      $startExpr AS START_DT_EFFECTIVE,
                      $stopExpr AS STOP_DT_EFFECTIVE
                      FROM ADD_TIME a
                      WHERE a.USERID='$userID_'
                        AND a.PAUSE_MODE = 1
                        AND (
                          $stopExpr > ADDDATE('$currentDate', INTERVAL -$paramInt DAY)
                          OR $stopExpr = '0000-00-00 00:00:00'
                        )
                      ORDER BY START_DT_EFFECTIVE DESC, a.ID DESC");

if (!$query) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
  $ta_id = $row["ID"];
  $ta_suir = $row["SUIR"];
  $ta_start_date = $row["START_DT_EFFECTIVE"];
  $ta_stop_date = $row["STOP_DT_EFFECTIVE"];
  $ta_reason = $row["REASON"];
  $ta_description = $row["DESCRIPTION"];
  $ta_approved = $row["APPROVED"];

  $ta_approved_str = "На рассмотрении";

  $superUserName = get_superuser_name_by_id( $ta_suir );

  $ta_reason_description = "Приостановка учета времени";

  if ( $colorMode == 0 ) {
    $color = $color1;
    $colorMode = 1;
  }
  else {
    $color = $color3;
    $colorMode = 0;
  }
                          
  if (is_time_defined($ta_stop_date) == 1) {
    $time_duration = format_time_(strtotime($ta_stop_date) - strtotime($ta_start_date));
  } else {
    $timeRes = get_current_datetime_in_timezone();
    $time_duration = format_time_(strtotime($timeRes[1]) - strtotime($ta_start_date));
    $ta_stop_date = "Активна";
  }
  	
  echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
echo "<td width=100 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($ta_start_date) . "</h5></td>";
echo "<td width=100 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($ta_stop_date) . "</h5></td>";
  echo "<td width=80  class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">".$time_duration."</h5></td>";
echo "<td width=160 class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($ta_description) . "</h5></td>";
echo "<td width=190 class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
  echo "</tr>";
}

echo "</table>";
echo "</div>";
?>
