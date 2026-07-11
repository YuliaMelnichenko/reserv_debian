<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID_ = (int)$_SESSION['ss_id'];
$currentDate = get_current_datetime_in_timezone_str( 1, 0 );
list($quarterStartDate, $quarterStopDate, $quarterStopExclusive) = get_current_quarter_date_range(false);
$quarterLabel = format_date_range_label($quarterStartDate, $quarterStopDate);
$startExpr = add_time_datetime_sql('a.START_DT', 'a.STARTDATE', 'a.STARTTIME', $link);
$stopExpr = add_time_datetime_sql('a.STOP_DT', 'a.STARTDATE', 'a.STOPTIME', $link);

echo "<h5 class=\"big\">Текущий квартал: $quarterLabel</h5>";
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
$color3 = "#ffffff";

mysqli_set_charset($link, "utf8");

$query = db_query(
  $link,
  "SELECT a.*,
          $startExpr AS START_DT_EFFECTIVE,
          $stopExpr AS STOP_DT_EFFECTIVE
   FROM ADD_TIME a
   WHERE a.USERID = ?
     AND a.PAUSE_MODE = 1
     AND $startExpr >= ?
     AND $startExpr < ?
     AND $startExpr <> '0000-00-00 00:00:00'
     AND $stopExpr <> '0000-00-00 00:00:00'
     AND $stopExpr > $startExpr
   ORDER BY START_DT_EFFECTIVE DESC, a.ID DESC",
  'iss',
  array($userID_, $quarterStartDate, $quarterStopExclusive)
);

if (!$query) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
  $ta_suir = $row["SUIR"];
  $ta_start_date = $row["START_DT_EFFECTIVE"];
  $ta_stop_date = $row["STOP_DT_EFFECTIVE"];
  $ta_description = $row["DESCRIPTION"];

  $superUserName = get_superuser_name_by_id( $ta_suir );

  if ( $colorMode == 0 ) {
    $color = $color1;
    $colorMode = 1;
  }
  else {
    $color = $color3;
    $colorMode = 0;
  }
                          
  $time_duration = format_time_(strtotime($ta_stop_date) - strtotime($ta_start_date));
  	
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
