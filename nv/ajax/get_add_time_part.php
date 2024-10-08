<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once "/var/www/tori/funcs.php";
include_once "/var/www/tori/php_tori/connect.php";

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


$content = "<table cellpadding=\"0\" cellspacing=\"0\" border=0 bordercolor=\"#888888\">";
$content .= "<tr>";
 $content .= "<td>";
 $content .= "<input checked type=\"radio\" style=\"background-color:#faefdd; border:0px;\" id=\"add_time_certain\" name=\"group2\"><h5 class=\"middle\">Задать единичную запись</h5><br>";
  $content .= "<table cellpadding=\"0\" cellspacing=\"0\" border=1 bordercolor=\"#888888\">";
  $content .= "<tr>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 120>"."<h5 class=\"bigDark\">Начало<br>(дата, время)"."</h5></td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 120>"."<h5 class=\"bigDark\">Окончание<br>(дата, время)"."</h5></td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 320>"."<h5 class=\"bigDark\">Основание"."</h5></td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 300>"."<h5 class=\"bigDark\">Комментарий"."</h5></td>";
  $content .= "</tr>";
  $content .= "<tr>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 120>";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_start_dateTime\" align=\"center\" style=\"width:100px;\" type=\"text\" value=\"$datetimeShort\">";
  $content .= "</td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 120>";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_stop_dateTime\" align=\"center\" style=\"width:100px;\" type=\"text\" value=\"$datetimeShort\">";
  $content .= "</td>";
  $content .= "<td valign=\"middle\" align=\"center\">";
  $content .= "<select id=\"add_time_part_base\" bgcolor=\"#888888\">";
   
  for ( $idx = 0; $idx < count( $reasonsArr ); $idx ++ )
  {
    $reasonArr = $reasonsArr[$idx];
    $id = $reasonArr[0];
    $desc = $reasonArr[1];
    $content .= "<option value=\"$id\">$desc</option>";
  }    
  
  $content .= "</select>";      
  $content .= "</td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 300 height = 70>";
  $content .= "<textarea id=\"add_time_part_desc\" style=\"width:280px; resize: none;\" cols=\"43\" rows=\"3\">".""."</textarea>";
  $content .= "</td>";
  $content .= "</tr>"; 
  $content .= "</table>";
 $content .= "</td>";
$content .= "</tr>";
$content .= "<tr>";
 $content .= "<td>";
 $content .= "<br><input type=\"radio\" style=\"background-color:#faefdd; border:0px;\" id=\"add_time_range\" name=\"group2\"><h5 class=\"middle\">Задать записи для диапазона дат</h5>";
  $content .= "<br><table cellpadding=\"0\" cellspacing=\"0\" border=1 bordercolor=\"#888888\">";
  $content .= "<tr>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 82>"."<h5 class=\"bigDark\">Начало<br>(дата)"."</h5></td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 82>"."<h5 class=\"bigDark\">Окончание<br>(дата)"."</h5></td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 110>"."<h5 class=\"bigDark\">Длительность<br>(чч:мм)"."</h5></td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 320>"."<h5 class=\"bigDark\">Основание"."</h5></td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 251>"."<h5 class=\"bigDark\">Комментарий"."</h5></td>";
  $content .= "</tr>";
  $content .= "<tr>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 82>";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_start_date\" align=\"center\" style=\"width:62px;\" type=\"text\" value=\"$dateStr\">";
  $content .= "</td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 82>";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_stop_date\" align=\"center\" style=\"width:62px;\" type=\"text\" value=\"$dateStr\">";
  $content .= "</td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 110>";
  $content .= "<h5 class=\"middle\"></h5><input id=\"add_time_part_duration\" align=\"center\" style=\"width:40px;\" type=\"text\" value=\"08:00\">";
  $content .= "</td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 250>";
  $content .= "<select id=\"add_time_part_base_1\" bgcolor=\"#888888\" width = 300 >";

  for ( $idx = 0; $idx < count( $reasonsArr ); $idx ++ )
  {
    $reasonArr = $reasonsArr[$idx];
    $id = $reasonArr[0];
    $desc = $reasonArr[1];
    $content .= "<option value=\"$id\">$desc</option>";
  }    

  $content .= "</select>";      
  $content .= "</td>";
  $content .= "<td valign=\"middle\" align=\"center\" width = 251 height = 70>";
  $content .= "<textarea id=\"add_time_part_desc_1\" style=\"width:231px; resize: none;\" cols=\"43\" rows=\"3\">".""."</textarea>";
  $content .= "</td>";
  $content .= "</tr>"; 
  $content .= "</table>";
  $content .= "<br><input checked type=\"checkbox\" id=\"exclude_weekend_holidays\" value=\"1\" ><h5 class=\"middle\"> Учитывать выходные и праздничные дни</h5>";
 $content .= "</td>";
$content .= "</tr>";
$content .= "</table><br>";  


$content .= "<table cellpadding=\"0\" cellspacing=\"0\" border=0 width=850>";  
$content .= "<tr>";

$content .= "<td bordercolor=\"#000000\" width=\"50%\" valign=\"middle\" align=\"left\">";
$content .= "<button style=\"font-size: 100%; width:178px; height:25px; background-color:#ff7979; border:1px solid #888888;\" onclick=\"cancel_part_time_add();\">Закрыть</button><br>";
$content .= "</td>";

$content .= "<td bordercolor=\"#000000\" width=\"50%\" valign=\"middle\" align=\"right\">";
$content .= "<button style=\"font-size: 100%; width:178px; height:25px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"part_time_add( '$byAlert' );\">Добавить</button><br>";
$content .= "</td>";

$content .= "</tr>";
$content .= "</table><br>";  

echo $content;
?>


                                                                         