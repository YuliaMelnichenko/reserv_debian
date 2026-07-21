<?php

require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$reasonsArr = get_reasons();

$retArr = get_current_datetime_in_timezone();

$datetime = $retArr[1];
$dateStr = $retArr[2];
$timeStr = $retArr[3];
$datetimeShort = substr( $datetime, 0, 16 );


if ( isset( $_POST['by_alert'] ) AND $_POST['by_alert'] == 1 )
{
  $byAlert = 1;
}
else
{
  $byAlert = 0;
}


$content = "<table class=\"add-time-part-form\">";
$content .= "<tr>";
 $content .= "<td>";
 $content .= "<div class=\"add_time_radio\">";
    $content .= "<input checked type=\"radio\" class=\"add-time-mode-radio\" id=\"add_time_certain\" name=\"group2\"><h5 class=\"middle\">Задать единичную запись</h5><br>";
 $content .= "</div>";
  $content .= "<table class=\"time_add_table add-time-part-single-table\">";
  $content .= "<tr>";
  $content .= "<td class=\"add-time-part-cell add-time-part-wide-cell\">"."<h5 class=\"bigDark\">Начало<br>(дата, время)"."</h5></td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-wide-cell\">"."<h5 class=\"bigDark\">Окончание<br>(дата, время)"."</h5></td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-wide-cell\">"."<h5 class=\"bigDark\">Основание"."</h5></td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-wide-cell\">"."<h5 class=\"bigDark\">Комментарий"."</h5></td>";
  $content .= "</tr>";
  $content .= "<tr>";
  $content .= "<td class=\"add-time-part-cell add-time-part-wide-cell\">";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_start_dateTime\" type=\"datetime-local\">";
  $content .= "</td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-wide-cell\">";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_stop_dateTime\" type=\"datetime-local\">";
  $content .= "</td>";
  $content .= "<td class=\"add-time-part-cell\">";
  $content .= "<select id=\"add_time_part_base\">";
   
  for ( $idx = 0; $idx < count( $reasonsArr ); $idx ++ )
  {
    $reasonArr = $reasonsArr[$idx];
    $id = $reasonArr[0];
    $desc = $reasonArr[1];
    $content .= "<option value=\"$id\">$desc</option>";
  }    
  
  $content .= "</select>";      
  $content .= "</td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-comment-cell\">";
  $content .= "<textarea id=\"add_time_part_desc\" cols=\"43\" rows=\"3\">".""."</textarea>";
  $content .= "</td>";
  $content .= "</tr>"; 
  $content .= "</table>";
 $content .= "</td>";
$content .= "</tr>";
$content .= "<tr>";
 $content .= "<td>";
 $content .= "<div class=\"add_time_radio\">";
    $content .= "<br><input type=\"radio\" class=\"add-time-mode-radio\" id=\"add_time_range\" name=\"group2\"><h5 class=\"middle\">Задать записи для диапазона дат</h5>";
 $content .= "</div>";
  $content .= "<table class=\"time_add_table add-time-part-range-table\">";
  $content .= "<tr>";
  $content .= "<td class=\"add-time-part-cell add-time-part-date-cell\">"."<h5 class=\"bigDark\">Начало<br>(дата)"."</h5></td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-date-cell\">"."<h5 class=\"bigDark\">Окончание<br>(дата)"."</h5></td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-time-cell\">"."<h5 class=\"bigDark\">Начало<br>(время)"."</h5></td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-time-cell\">"."<h5 class=\"bigDark\">Окончание<br>(время)"."</h5></td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-wide-cell\">"."<h5 class=\"bigDark\">Основание"."</h5></td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-range-comment-cell\">"."<h5 class=\"bigDark\">Комментарий"."</h5></td>";
  $content .= "</tr>";
  $content .= "<tr>";
  $content .= "<td class=\"add-time-part-cell add-time-part-date-cell\">";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_start_date\" type=\"date\">";
  $content .= "</td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-date-cell\">";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_stop_date\" type=\"date\">";
  $content .= "</td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-time-cell\">";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_start_time\" type=\"time\">";
  $content .= "</td>";

  $content .= "<td class=\"add-time-part-cell add-time-part-time-cell\">";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_stop_time\" type=\"time\">";
  $content .= "</td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-range-base-cell\">";
  $content .= "<select id=\"add_time_part_base_1\">";

  for ( $idx = 0; $idx < count( $reasonsArr ); $idx ++ )
  {
    $reasonArr = $reasonsArr[$idx];
    $id = $reasonArr[0];
    $desc = $reasonArr[1];
    $content .= "<option value=\"$id\">$desc</option>";
  }    

  $content .= "</select>";      
  $content .= "</td>";
  $content .= "<td class=\"add-time-part-cell add-time-part-range-text-cell\">";
  $content .= "<textarea id=\"add_time_part_desc_1\" cols=\"43\" rows=\"3\">".""."</textarea>";
  $content .= "</td>";
  $content .= "</tr>"; 
  $content .= "</table>";
  $content .= "<br><input checked type=\"checkbox\" id=\"exclude_weekend_holidays\" value=\"1\" ><h5 class=\"middle\"> Учитывать выходные и праздничные дни</h5>";
 $content .= "</td>";
$content .= "</tr>";
$content .= "</table><br>";  


$content .= "<table class=\"add-time-part-actions\">";
$content .= "<tr>";

$content .= "<td class=\"add-time-part-action-left\">";
$content .= "<button class=\"journal-action-button journal-action-button-wide journal-action-button-close\" onclick=\"cancel_part_time_add();\">Закрыть</button><br>";
$content .= "</td>";

$content .= "<td class=\"add-time-part-action-right\">";
$content .= "<button class=\"journal-action-button journal-action-button-wide\" onclick=\"part_time_add( $byAlert );\">Добавить</button><br>";
$content .= "</td>";

$content .= "</tr>";
$content .= "</table><br>";  

echo $content;
?>
