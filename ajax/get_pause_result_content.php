<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = (int)$_SESSION['ss_id'];
$currentDate = date('Y-m-d');

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

$query = time_journal_query_latest_completed_pause($link, $userID, $currentDate);

$merr = mysqli_error($link);

if (!$query)
{
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
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
      $startDateTime = $row["START_DT"];
      $stopDateTime = $row["STOP_DT"];
      $startTime = datetime_to_time_str($startDateTime);
      $stopTime = datetime_to_time_str($stopDateTime);
      $desk = $row["DESCRIPTION"];
      $SUName = get_superuser_name_by_id( $suid );
      $duration = get_defined_time_range_duration($startDateTime, $stopDateTime);
      $durationStr = format_time_d_hhmmss_pure( $duration );

            echo "<table id=\"resultContentTable\" border=\"1\">";
              echo "<tr>";
                echo "<td align=\"left\" width = \"450\">";
                  echo "<h5 class=\"bigbig1\">Учет времени возобновлен</h5>";
                echo "</td>";
              echo "</tr>";
              echo "<tr>";
                echo "<td>";

                  echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0 style=\"margin:0; padding:0; margin-left:0;\" >";
                    echo "<tr>";
                      echo "<td valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">время начала</h5>";
                      echo "</td>";
                    echo "<td valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">" . html_escape($startTime) . "</h5>";
                      echo "</td>";
                    echo "</tr>";

                    echo "<td valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">время окончания</h5>";
                      echo "</td>";
                    echo "<td valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">" . html_escape($stopTime) . "</h5>";
                      echo "</td>";
                    echo "</tr>";


                    echo "<tr>";
                      echo "<td valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">длительность</h5>";
                      echo "</td>";
                      echo "<td valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">" . html_escape($durationStr) . "</h5>";
                      echo "</td>";
                    echo "</tr>";

                    echo "<tr>";
                      echo "<td valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">согласовано</h5>";
                      echo "</td>";
                      echo "<td valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">" . html_escape($SUName) . "</h5>";
                      echo "</td>";
                    echo "</tr>";

                    echo "<tr>";
                      echo "<td valign=\"middle\" align=\"left\">";
                        echo "<h5 class=\"big\">комментарий</h5>";
                      echo "</td>";
                      echo "<td valign=\"middle\" align=\"left\">";
echo "<h5 class=\"big\">" . html_escape($desk) . "</h5>";
                      echo "</td>";
                    echo "</tr>";
                  echo "</table>";

                echo "</td>";
              echo "</tr>";
              echo "<tr>";
                echo "<td valign=\"middle\" align=\"center\">";
                   echo "<button style=\"margin:0; padding:0; font-size: 100%; width:390px; height:30px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"close_pause_result_head();\">Закрыть</button>";
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
