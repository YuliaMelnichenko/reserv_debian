<?php
ob_start();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/style.css\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body bgcolor=\"#ffffff\" >";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" src="js/tory.js"></script>

<?php
session_start();
////////////////////////////////////////////////////////
include_once "/var/www/tori/funcs.php";
include "/var/www/tori/php_tori/connect.php";

save_last_location( "time_add.php" );
auth();
////////////////////////////////////////////////////////

echo "<div align=\"left\">";
echo "<table border=0>";
echo "<tr>";
echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";

include_once "/var/www/tori/navigate.php";

echo "</td>";
   
$wholeWidth = 700;

echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

echo "<h5 class=\"dark\"><br>/График отпусков сотрудников ООО НПФ \"ТОРИ\"<br><br></h5>";

echo "<table id=\"\" border=1>";
echo "<tr bgcolor=\"#DDDDDD\" bordercolor=\"#615959\" height=\"20px\">";
echo "<td class=\"add_time_sport\" valign=\"middle\" align=\"center\">";
echo "<div class=\"person_holiday\">"."<h5 class=\"data_train\">Сотрудник</h5>"."</div>";
echo "</td>";
echo "<td class=\"add_time_sport\" valign=\"middle\" align=\"center\">";
echo "<div class=\"date_holiday\">"."<h5 class=\"data_train\">Отпускные дни</h5>"."</div>";
echo "</td>";
echo "<td class=\"add_time_sport\" valign=\"middle\" align=\"center\">";
echo "<div class=\"sum_holiday\">"."<h5 class=\"data_train\">Количество дней</h5>"."</div>";
echo "</td>";

mysqli_set_charset($link, "utf8");

$query = mysqli_query($link, "SELECT *, GROUP_CONCAT(CONCAT(DATE_FORMAT(start_date, '%d.%m'), ' - ', DATE_FORMAT(stop_date, '%d.%m')) SEPARATOR ' ') AS dates, SUM(sum) FROM holiday GROUP BY user_id ORDER BY user_id");

while ($row = mysqli_fetch_assoc($query)) {
  $id = $row["user_id"];
  $start_date_holiday = $row["start_date"];
  $stop_date_holiday = $row["stop_date"];
  $dates = wordwrap($row["dates"], 15, "<br />");
  $sum_holiday = $row["total_sum"];

  $query2 = mysqli_query($link, "SELECT surname, firstname FROM employees WHERE id = '$id'");
  $row2 = mysqli_fetch_assoc($query2);
  $firstname = $row2["firstname"];
  $surname = $row2["surname"];

  echo "<tr bgcolor=\"#ddeeff\" bordercolor=\"#615959\" height=\"30px\">";
  echo "<td width=310 valign=\"middle\" align=\"center\"><h2 class=\"holiday\">$surname "." $firstname</h2></td>";
  echo "<td width=150 valign=\"middle\" align=\"center\"><h2 class=\"holiday\">$dates</h2></td>";
  echo "<td width=150 valign=\"middle\" align=\"center\"><h2 class=\"holiday\">$sum_holiday</h2></td>";
  echo "</tr>";
}

echo "</tr>";
echo "</table>";
echo "</tr>";
echo "</table>";
echo "</div>";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" charset="utf-8">


function update_clock() {
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat) {
    if ( document.getElementById('dateTimeFieldNav')) {
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId=setInterval( "update_clock()", 1000 );
</script>

<?php
echo "</body>";
echo "</html>";
?>

