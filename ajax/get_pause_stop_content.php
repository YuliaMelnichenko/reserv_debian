<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id']; 

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$dtResult = get_current_datetime_in_timezone(); 
$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];


mysqli_set_charset($link, "utf8");

error_reporting(E_ALL | E_STRICT) ;
ini_set('display_errors', 'On');

$query = mysqli_query($link, "SELECT ID, SUIR, START_DT, DESCRIPTION FROM ADD_TIME WHERE ADDDATE = '$currentDate' AND USERID = '$userID' AND PAUSE_MODE = 1 ORDER BY ADDDATE DESC, START_DT DESC LIMIT 1");


$merr=mysqli_error($link);
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  $vn=mysqli_num_rows($query);
  if ( $vn == 0 )
  {
    echo "0";
  } 
  else
  {
    if ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {  
      $id = $row["ID"];
      $suid = $row["SUIR"];
      $startTime = $row["START_DT"];
      $desk = $row["DESCRIPTION"];
      $SUName = get_superuser_name_by_id( $suid );
      $duration = strtotime( $currentDateTime ) - strtotime( $startTime );
      $durationStr = format_time_d_hhmmss_pure( $duration );

      echo "<table bgcolor=\"#FFFFFF\" id=\"pauseFullScreen\">";
        echo "<tr>";
          echo "<td align= \"center\" valign=\"middle\">";
            ///
            echo "<table class=\"add_time\" border=\"0\" bgcolor=\"#ddeeff\">";  
              echo "<tr>";
                echo "<td align=\"left\" width = \"250\">";

                  echo "<h5 class=\"bigbig1\"><br>Учет времени приостановлен<br><br></h5>";
                echo "</td>";
              echo "</tr>";
              echo "<tr>";
                echo "<td class=\"report_no_padding_no_border\">";  

                  echo "<table class=\"no_padding_real\" width=450 >";  
                    echo "<tr>";
                      echo "<td class=\"report_no_padding\" valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">время начала</h5>";
                      echo "</td>";
                      echo "<td class=\"report_no_padding\" valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">$startTime</h5>";
                      echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr bgcolor=\"#ffffff\">";
                      echo "<td class=\"report_no_padding\" valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">длительность</h5>";
                      echo "</td>";
                      echo "<td class=\"report_no_padding\" valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">$durationStr</h5>";
                      echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr>";
                      echo "<td class=\"report_no_padding\" valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">согласовано</h5>";
                      echo "</td>";
                      echo "<td class=\"report_no_padding\" valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">$SUName</h5>";
                      echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr bgcolor=\"#ffffff\">";
                      echo "<td class=\"report_no_padding\" valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">комментарий</h5>";
                      echo "</td>";
                      echo "<td class=\"report_no_padding\" valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">$desk</h5>";
                      echo "</td>";
                    echo "</tr>";
                  echo "</table>";

                echo "</td>";
              echo "</tr>";
              echo "<tr>";
                echo "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
                   echo "<br><button style=\"margin:0; padding:0; font-size: 100%; width:390px; height:30px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"resume_from_pause( '$id' );\">Возобновить учет времени</button><br><br>";
                echo "</td>";
              echo "</tr>";
            echo "</table>"; 
            ///
          echo "</td>"; 
        echo "</tr>";
      echo "</table>";
    }
  }

  echo "<script type=\"text/javascript\" charset=\"utf-8\">"; 
  echo "set_pause_full_screen();";
  echo "window.onresize = function() { set_pause_full_screen(); }";
  echo "</script>";
}
?>