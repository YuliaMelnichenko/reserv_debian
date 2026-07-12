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
<script type="text/javascript" src="js/tory.js"></script>

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

echo "<h5 class=\"dark\"><br>/тренажерный зал<br><br></h5>";

echo "<div id=\"pause_sport_times_table\">";
echo "</div>"; 

echo "<div id=\"delay_explanation_sport_time\">";
echo "</div>";

echo "<div id=\"delete_gym_schedule_window\">";
echo "</div>";

echo "</td>";
echo "<td class=\"sport-pause-spacer-cell\">";
echo "</td>";
 
echo "<td class=\"sport-pause-help-cell\">";
echo "<h5 class=\"dark0\"><br>/рекомендации по планированию тренировок:<br><br></h5>";
echo "<h5 class=\"dark1\">1. Для планирования тренировки или нескольких тренировок, нажмите кнопку \"Запланировать\".<br></h5>";
echo "<h5 class=\"dark1\">2. В открывшемся окне введите дату планируемой тренировки (или выберите дату нажатием на иконку календаря).<br></h5>";
echo "<h5 class=\"dark1\">3. Введите время начала и время окончания тренировки. Обратите внимаение на:<br></h5>";
echo "<h5 class=\"dark1\">- Время окончания не должно быть меньше времени начала.<br></h5>";
echo "<h5 class=\"dark1\">- Если планируете тренировку на сегодня, то время начала должно быть больше текущего времени.<br></h5>";
echo "<h5 class=\"dark1\">4. Для удаления запланированных тренировок нажмите кнопку \"Удалить запись\" и выберите тренировку для удаления. Кнопка активна только если у вас имеются запланированные тренировки.<br></h5>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";
?>

<script type="text/javascript" charset="utf-8">

show_pause_sport_table();

function update_clock(){
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat){
    if ( document.getElementById('dateTimeFieldNav') ){
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
