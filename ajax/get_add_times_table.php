<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID = $_SESSION['ss_id']; 

echo "<table border=0>";
echo "<tr bgcolor=\"#ddeedd\" bordercolor=\"#888888\">";

echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" height = 30>";
echo "<button style=\" cursor: pointer; font-size: 80%; width:150px; height:25px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"as_add_time();\">Добавить время</button><br>";
echo "</td>";    
echo "</tr>";    
echo "<tr>";    

echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 0>";
echo "<table class=\"add_time\">";
echo "<tr>";

echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Начало<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Окончание<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Длительность</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Основание</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Комментарий работника</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Лицо, принявшее<br>решение</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Комментарий лица,<br>принявшего решение</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Статус</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Управление</h5>"."</td>";
echo "</tr>";
  
$colorMode = 1;
$color1 = "#ddffff";
$color2 = "#ddeedd";
$color3 = "#ffffff";

$addTimeInfo = get_all_add_work_info_by_user( $userID, 0 );

for ( $idx = 0; $idx < count( $addTimeInfo ); $idx ++ )
{
  $addInf = $addTimeInfo[$idx];

  $ta_id = $addInf[8];
  $ta_start_dt = $addInf[0];
  $ta_stop_dt = $addInf[1];

  $ta_reason_description = $addInf[11];
  $ta_description = $addInf[3];
  $ta_SUdescription = $addInf[10];
  $ta_approved = $addInf[4];
  $ta_suir = $addInf[5];
  $pauseMode = $addInf[7];

  if ( $pauseMode == 1 ){ continue; }    
  if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 ){ continue; }    

  $ta_approved_str = "На рассмотрении";

  $superUserName = get_superuser_name_by_id( $ta_suir );

  if ( $ta_approved == 0 )
  { 
    $approvedStr = "<h5 class=\"middleBold_r\">на рассмотрении</h5>";
  }
  else if ( $ta_approved == -1 )
  { 
    $approvedStr = "<h5 class=\"middleBold_r\">отклонено</h5>";
  }
  else if ( $ta_approved == 1 )
  { 
    $approvedStr = "<h5 class=\"middleBold_r\">принято</h5>";
  }   

  $time_duration = format_time_( strtotime($ta_stop_dt) - strtotime($ta_start_dt) );
  	
  if ( $colorMode == 0 )
  {
    $color = $color1;
    $colorMode = 1;
  }
  else
  {
    $color = $color3;
    $colorMode = 0;
  }

  $buttonAdd1 = "";
  $buttonAdd2 = "onclick=\"ta_delete('$ta_id');\"";
  $buttonAdd3 = "title=\"удалить запись\"";

  $bgcolor = "";

  if ( $ta_approved == -1 )
  {
    $buttonAdd1 = "disabled";
    $bgcolor = "#FFAAAA";
    $buttonAdd2 = "onclick=\"alert( 'запись уже заквитирована. Удаление невозможно');\"";
    $buttonAdd3 = "title=\"запись уже заквитирована. Удаление невозможно\"";
  }
  if ( $ta_approved == 0 )
  {
    $classColor = "big";
  }
  if ( $ta_approved == 1 )
  {
    $buttonAdd1 = "disabled";
    $bgcolor = "#AAFFAA";
    $buttonAdd2 = "onclick=\"alert( 'запись уже заквитирована. Удаление невозможно');\"";
    $buttonAdd3 = "title=\"запись уже заквитирована. Удаление невозможно\"";
  }

  echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
  echo "<td class=\"add_time\" width=100 valign=\"middle\" align=\"center\"><h5 class=\"small\">$ta_start_dt</h5></td>";
  echo "<td class=\"add_time\" width=100 valign=\"middle\" align=\"center\"><h5 class=\"small\">$ta_stop_dt</h5></td>";
  echo "<td class=\"add_time\" width=85  valign=\"middle\" align=\"center\"><h5 class=\"small\">".$time_duration."</h5></td>";
  echo "<td class=\"add_time\" width=100 valign=\"middle\" align=\"left\"><h5 class=\"small\">".$ta_reason_description."</h5></td>";
  echo "<td class=\"add_time\" width=140 valign=\"middle\" align=\"left\"><h5 class=\"small\">".$ta_description."</h5></td>";

  echo "<td class=\"add_time\" width=140 valign=\"middle\" align=\"left\">"."<h5 class = \"small\">$superUserName</h5>"."</td>";
  echo "<td class=\"add_time\" width=140 valign=\"middle\" align=\"left\"><h5 class=\"small\">".$ta_SUdescription."</h5></td>";
  echo "<td class=\"add_time\" width=115 bgcolor=\"$bgcolor\" valign=\"middle\" align=\"center\">$approvedStr</td>";
  echo "<td class=\"add_time\" width=105 valign=\"middle\" align=\"center\">";
    echo "<button style=\"font-size: 80%; width:100px; height:25px; background-color:#f8d888; border:1px solid #888888;\" $buttonAdd1 $buttonAdd2 $buttonAdd3 name=\"nextBtn\">Удалить</button>";
  echo "</td>";
  echo "</tr>";
}

echo "</table>";
?>