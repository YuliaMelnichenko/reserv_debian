<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . "/../inc/pause_journal.php";

$userID_ = (int)$_SESSION['ss_id'];
$journal = get_pause_journal_context($link, $userID_, get_current_datetime_in_timezone_str(1, 0));

if ($journal === false) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

if ($journal === null) {
  deny_ajax_access(404, 'USER_NOT_FOUND');
}

$quarterLabel = format_date_range_label($journal['quarter_start_date'], $journal['quarter_stop_date']);
$pauseEntries = $journal['entries'];

echo "<h5 class=\"big\">Текущий квартал: $quarterLabel</h5>";
echo "<div class=\"notification-table-scroll notification-table-scroll-medium\">";
echo "<table class=\"add_time journal-entry-table\">";
echo "<tr class=\"journal-entry-head\">";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Начало<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Окончание<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Длительность</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Комментарий<br></h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>С кем предварительно<br>согласовано</h5>"."</td>";
echo "</tr>";
  
$colorMode = 1;
$color1 = "#ddffff";
$color3 = "#ffffff";

foreach ($pauseEntries as $pauseEntry) {
  $ta_start_date = $pauseEntry['start_datetime'];
  $ta_stop_date = $pauseEntry['stop_datetime'];
  $ta_description = $pauseEntry['employee_comment'];
  $superUserName = $pauseEntry['supervisor_name'];

  if ( $colorMode == 0 ) {
    $color = $color1;
    $colorMode = 1;
  }
  else {
    $color = $color3;
    $colorMode = 0;
  }
                          
  $time_duration = format_time_($pauseEntry['duration']);
  	
  $rowClass = $color == $color1 ? "journal-entry-row-alt" : "journal-entry-row";

  echo "<tr class=\"$rowClass\">";
echo "<td class=\"add_time journal-entry-date-cell\"><h5 class=\"small\">" . html_escape($ta_start_date) . "</h5></td>";
echo "<td class=\"add_time journal-entry-date-cell\"><h5 class=\"small\">" . html_escape($ta_stop_date) . "</h5></td>";
  echo "<td class=\"add_time journal-entry-duration-cell\"><h5 class=\"small\">".$time_duration."</h5></td>";
echo "<td class=\"add_time journal-entry-pause-comment-cell\"><h5 class=\"small\">" . html_escape($ta_description) . "</h5></td>";
echo "<td class=\"add_time journal-entry-pause-supervisor-cell\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
  echo "</tr>";
}

echo "</table>";
echo "</div>";
?>
