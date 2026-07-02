<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$wholeWidth = $_POST['width'];
$widthOffs = $_POST['offs'];

$dtvalStr = get_current_datetime_in_timezone_str( 1, 1 );
$WidthLeft = 300;
$WidthRight = $wholeWidth - $WidthLeft - $widthOffs;

echo "<table>";
  echo "<tr>";
    echo "<td valign=\"middle\" align=\"left\" width = $WidthLeft height = 10>";
      echo "<h5 class=\"dark\"><br>/уведомления по работе вне офиса<br><br></h5>";
    echo "</td>";
    echo "<td valign=\"middle\" align=\"right\" width = $WidthRight height = 10>";
      echo "<div id=\"dateTimeField\">";
        echo "<font size=\"4\" color=\"#000000\" face=\"Arial\">".$dtvalStr."</font>";
      echo "</div>";
    echo "</td>";
  echo "</tr>";
echo "</table>";
?>