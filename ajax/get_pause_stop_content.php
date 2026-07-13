<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = (int)$_SESSION['ss_id'];

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$dtResult = get_current_datetime_in_timezone(); 
$currentDate = $dtResult[2];
$currentDateTime = $dtResult[1];


mysqli_set_charset($link, "utf8");

$query = db_query(
  $link,
  'SELECT ID, SUIR, START_DT, DESCRIPTION FROM ADD_TIME WHERE ADDDATE = ? AND USERID = ? AND PAUSE_MODE = 1 ORDER BY ADDDATE DESC, START_DT DESC LIMIT 1',
  'si',
  array($currentDate, $userID)
);


$merr=mysqli_error($link);
if (!$query)
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
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
      $id = (int)$row["ID"];
      $suid = (int)$row["SUIR"];
      $startTime = $row["START_DT"];
      $desk = $row["DESCRIPTION"];
      $SUName = get_superuser_name_by_id( $suid );
      $duration = strtotime( $currentDateTime ) - strtotime( $startTime );
      $durationStr = format_time_d_hhmmss_pure( $duration );

      echo "<table id=\"pauseFullScreen\" class=\"pause-overlay-table\">";
        echo "<tr>";
          echo "<td class=\"pause-overlay-cell\">";
            ///
            echo "<table class=\"add_time pause-state-card\">";
              echo "<tr>";
                echo "<td class=\"pause-state-title\">";

                  echo "<h5 class=\"bigbig1\"><br>Учет времени приостановлен<br><br></h5>";
                echo "</td>";
              echo "</tr>";
              echo "<tr>";
                echo "<td class=\"report_no_padding_no_border\">";  

                  echo "<table class=\"no_padding_real pause-state-details\">";
                    echo "<tr>";
                      echo "<td class=\"report_no_padding pause-state-label\">";
                        echo "<h5 class=\"big\">время начала</h5>";
                      echo "</td>";
                      echo "<td class=\"report_no_padding pause-state-value\">";
                        echo "<h5 class=\"big\">" . html_escape($startTime) . "</h5>";
                      echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr class=\"pause-state-row-alt\">";
                      echo "<td class=\"report_no_padding pause-state-label\">";
                        echo "<h5 class=\"big\">длительность</h5>";
                      echo "</td>";
                      echo "<td class=\"report_no_padding pause-state-value\">";
                        echo "<h5 class=\"big\">" . html_escape($durationStr) . "</h5>";
                      echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr>";
                      echo "<td class=\"report_no_padding pause-state-label\">";
                        echo "<h5 class=\"big\">согласовано</h5>";
                      echo "</td>";
                      echo "<td class=\"report_no_padding pause-state-value\">";
                        echo "<h5 class=\"big\">" . html_escape($SUName) . "</h5>";
                      echo "</td>";
                    echo "</tr>";
                    
                    echo "<tr class=\"pause-state-row-alt\">";
                      echo "<td class=\"report_no_padding pause-state-label\">";
                        echo "<h5 class=\"big\">комментарий</h5>";
                      echo "</td>";
                      echo "<td class=\"report_no_padding pause-state-value\">";
echo "<h5 class=\"big\">" . html_escape($desk) . "</h5>";
                      echo "</td>";
                    echo "</tr>";
                  echo "</table>";

                echo "</td>";
              echo "</tr>";
              echo "<tr>";
                echo "<td class=\"report_no_padding pause-state-action-cell\">";
                   echo "<br><button class=\"pause-state-action\" onclick=\"resume_from_pause($id);\">Возобновить учет времени</button><br><br>";
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
