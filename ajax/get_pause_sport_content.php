<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = (int)$_SESSION['ss_id'];

include_once __DIR__ . "/../funcs.php";

echo "<div id=\"add_sport_pause\">";
  echo "<div id =\"close_window_sport_pause\">";
    echo "<button class=\"pause-dialog-close\" title=\"Закрыть\" onclick=\"close_sport_pause();\"><img src=\"img/closeSmall.png\" alt=\"\"></button>";
  echo "</div>";
  echo "<div>";
    echo "<h5 class=\"big\">Причина приостановки</h5>";
  echo "</div>";
  echo "<div>";
    echo "<select class=\"pause-sport-select\">";
      echo "<option>Тренажерный зал</option>";
    echo "</select>"; 
  echo "</div>";
  echo "<div>";
    echo "<h5 class=\"big\"><br>Комментарий</h5>";
  echo "</div>";
  echo "<div>";
    echo "<textarea id=\"pause_desk\" class=\"pause-sport-textarea\" cols=\"43\" rows=\"3\">Посещение тренажерного зала</textarea>";
  echo "</div>";
  echo "<div>";
  echo "<br><button id=\"sport_pause_btn\" onclick=\"set_pause_sport_state();\">Приостановка учета времени</button>";
  echo "</div>";
echo "</div>";
?>
