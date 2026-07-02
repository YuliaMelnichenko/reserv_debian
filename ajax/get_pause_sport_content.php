<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id'];

include_once __DIR__ . "/../funcs.php";

echo "<div id=\"add_sport_pause\">";
  echo "<div id =\"close_window_sport_pause\">";
    echo "<img onclick=\"close_sport_pause();\" src=\"img/closeSmall.png\" style=\"cursor:pointer\">";
  echo "</div>";
  echo "<div>";
    echo "<h5 valign=\"middle\" align=\"left\" class=\"big\">Причина приостановки</h5>";
  echo "</div>";
  echo "<div>";
    echo "<select bgcolor=\"#888888\" style=\"width:260px; border:1px solid #888888;\" >";
      echo "<option>Тренажерный зал</option>";
    echo "</select>"; 
  echo "</div>";
  echo "<div>";
    echo "<h5 valign=\"middle\" align=\"left\" class=\"big\"><br>Комментарий</h5>";
  echo "</div>";
  echo "<div>";
    echo "<textarea id=\"pause_desk\" style=\"width:254px; resize: none;\" cols=\"43\" rows=\"3\">Посещение тренажерного зала</textarea>";
  echo "</div>";
  echo "<div>";
  echo "<br><button id=\"sport_pause_btn\" onclick=\"set_pause_sport_state();\">Приостановка учета времени</button>";
  echo "</div>";
echo "</div>";
?>