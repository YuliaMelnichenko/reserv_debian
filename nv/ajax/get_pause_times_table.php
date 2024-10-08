<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

include_once "/var/www/tori/nv/funcs.php";
include_once "/var/www/tori/nv/php_tori/connect.php";

$currentDate = get_current_datetime_in_timezone_str( 1, 0 );

$userID_ = $_SESSION['ss_id']; 

$paramArr = get_dbsetup_param( 'pause_journal_deep_day' );
  
$paramInt = $paramArr[1];

$paramIntSign = (-1)*$paramArr[1];

echo "<h5 class=\"big\"> Глубина просмотра журнала (дни): $paramInt</h5>";

echo "<table class=\"add_time\" border=1>";
echo "<tr bgcolor=\"#DDDDDD\" bordercolor=\"#888888\">";

echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Начало<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Окончание<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Длительность</h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Комментарий<br></h5>"."</td>";
echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>С кем предварительно<br>согласовано</h5>"."</td>";
echo "</tr>";
  
$colorMode = 1;
$color1 = "#ddffff";
$color2 = "#ddeedd";
$color3 = "#ffffff";

mysqli_set_charset($link, "utf8");

$query = mysqli_query($link, "SELECT * FROM ADD_TIME 
                      WHERE   
                      USERID='$userID_'
                        and
                      STOP_DT > ADDDATE( '$currentDate', INTERVAL $paramIntSign DAY ) 
                        and 
                      pause_mode = 1
                      ORDER BY ID DESC"); 

while($row = mysqli_fetch_array($query, MYSQLI_ASSOC))
{
  $ta_id = $row["ID"];
  $ta_suir = $row["SUIR"];
  $ta_start_date = $row["START_DT"];
  $ta_stop_date = $row["STOP_DT"];
  $ta_reason = $row["REASON"];
  $ta_description = $row["DESCRIPTION"];
  $ta_approved = $row["APPROVED"];

  $ta_approved_str = "На рассмотрении";

  $superUserName = get_superuser_name_by_id( $ta_suir );

  $ta_reason_description = "Приостановка учета времени";

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
                          
  $time_duration = format_time_( strtotime($ta_stop_date) - strtotime($ta_start_date) );
  	
  echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
  echo "<td width=100 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">$ta_start_date</h5></td>";
  echo "<td width=100 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">$ta_stop_date</h5></td>";
  echo "<td width=80  class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">".$time_duration."</h5></td>";
  echo "<td width=160 class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"small\">".$ta_description."</h5></td>";
  echo "<td width=190 class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"small\">".$superUserName."</h5></td>";
  echo "</tr>";
}

echo "</table>";
?>


                                                                         