<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body class=\"app-page\">";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" src="js/tory.js?v=20260709-filter"></script>

<?php
////////////////////////////////////////////////////////
include_once __DIR__ . "/funcs.php";
save_last_location( "time_add.php" );
auth();
////////////////////////////////////////////////////////

echo "<div id=\"delay_explanation_head\">";
echo "</div>";

echo "<div class=\"notification-page-layout\">";

echo "<table class=\"notification-page-table\">";
echo "<tr>";

echo "<td class=\"notification-nav-cell\">";

include_once __DIR__ . "/navigate.php";

echo "</td>";

echo "<td class=\"notification-content-cell notification-content-cell-medium\">";

echo "<h5 class=\"dark\"><br>/приостановки учета времени<br><br></h5>";

echo "<div id=\"pause_times_table\">";
echo "</div>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";
?>

<script type="text/javascript" charset="utf-8">

show_pause_table();

function update_clock()
{
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat)
  {
    if ( document.getElementById('dateTimeFieldNav') )
    {
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId = setInterval(update_clock, 1000);
</script>

<?php
echo "</body>";
echo "</html>";
?>
