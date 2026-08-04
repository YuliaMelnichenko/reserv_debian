<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = $_SESSION['ss_id']; 
$currentDate = date('Y-m-d');

include_once __DIR__ . "/../funcs.php";

$last_day = date("Y-m-d", strtotime('yesterday'));
$last_days = date("Y-m-d", strtotime('-3 days')); 

$monday = GetWeekDayD($currentDate);

$time = "19:00";

$full_date = $last_days." ".$time;
$full_last_date = $last_day." ".$time;


echo "<div class = \"reg_out_time\">";
  
  echo "<div class = \"reg_out_time_head\">";
    echo "<div class = \"reg_out_time_text\">";
      echo "<h5 class=\"big\">Введите дату и время прихода с обеда.</h5>";
    echo "</div>";
    echo "<div class = \"reg_out_time_close\">";
      echo "<img onclick=\"close_out_time();\" src=\"img/closeSmall.png\">";
    echo "</div>";
  echo "</div>";

  if ($monday == "1") {
    echo "<div class = \"reg_out_time_body\">";
      echo "<textarea id=\"add_stop_eat_time\" cols=\"33\" rows=\"2\">$full_date</textarea>";
    echo "</div>";
  }

  else {
    echo "<div class = \"reg_out_time_body\">";
      echo "<textarea id=\"add_stop_eat_time\" cols=\"33\" rows=\"2\">$full_last_date</textarea>";
    echo "</div>";
  }

  echo "<div class = \" reg_out_time_footer\">";
    echo "<div class = \" reg_out_time_save\">";
      echo "<button class = \"reg_out_time_button_save\" onclick=\"save_changes_time($userID, $monday);\">Сохранить</button>";
    echo "</div>";
    echo "<div class = \"reg_out_time_close\">";
      echo "<button class = \"reg_out_time_button_close\" onclick=\"close_out_time();\">Отмена</button>";
    echo "</div>";
  echo "</div>";

echo "</div>";

?>