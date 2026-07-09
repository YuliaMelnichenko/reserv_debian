<?php

require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";

$example = date("Y-m-d", strtotime('today'));
$time = get_current_datetime_in_timezone_str(1,0);
$timestamp = strtotime($time);
$allTime = date('H:i', $timestamp);
$time_start = "19:00";
$time_stop = "20:00";
$full_example = $example." ".$time_start ."-".$time_stop;

$content = "<div id=\"gym_schedule\">";
  $content .= "<div class=\"schedule_text\">";
  $content .= "<h5>Внесите день и временной интервал вашей тренировки.</h5>";
  $content .= "</div>";
  $content .= "<div class=\"training_time_box\">";
  $content .= "<div class=\"enter_training_box\">";
  $content .= "<h5>Дата</h5>";
  $content .= "<input id=\"enter_training_date\" align=\"middle\" style=\"width:110px;\" type=\"date\" name=\"trip-start\" value=$example min=$example max=\"2060-12-31\">";
  $content .= "</div>";
  $content .= "<div class=\"enter_training_box\">";
  $content .= "<h5>Время начала</h5>";
  $content .= "<input id=\"enter_training_start_time\" align=\"middle\" style=\"width:110px;\" type=\"time\" value=$time_start>";
  $content .= "</div>";
  $content .= "<div class=\"enter_training_box\">";
  $content .= "<h5>Время окончания</h5>";
  $content .= "<input id=\"enter_training_stop_time\" align=\"middle\" style=\"width:110px;\" type=\"time\" value=$time_stop>";
  $content .= "</div>";
$content .= "</div>"; 

$content .= "<div id=\"buttonBox\">";
$content .= "<div class=\"add_train_btn\">";
$content .= "<button style=\"cursor: pointer; font-size: 100%; width:90px; height:25px; background-color:#ff7979; border:1px solid #888888;\" onclick=\"close_add_sport_time();\">Закрыть</button><br>";
$content .= "</div>";
$content .= "<div class=\"add_train_btn\">";
$content .= "<button style=\"cursor: pointer; font-size: 100%; width:90px; height:25px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"save_entry('$example', '$allTime');\">Добавить</button><br>";
$content .= "</div>";
$content .= "</div>";

echo $content;
?>