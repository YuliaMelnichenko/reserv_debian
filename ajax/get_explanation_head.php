<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
  echo "<tr height = 30 valign=\"middle\">";
    echo "<td valign=\"middle\" align=\"left\" width = 430>";
      echo "<h5 class=\"big\"><br>Опоздание обусловлено выполнением работ вне офиса?</h5>";
    echo "</td>";
    echo "<td valign=\"middle\" align=\"right\" width = 70>";
      echo "<img onclick=\"close_explanation_head(); location.reload();\" src=\"img/close.png\">";
    echo "</td>";
  echo "</tr>";
echo "</table>";        

echo "<br><table cellpadding=\"0\" cellspacing=\"0\" border=0>";
  echo "<tr width = 450>";
    echo "<td valign=\"middle\" align=\"left\" width = 250>";
      echo "<button style=\"font-size: 100%; width:130px; height:25px; background-color:#91f591; border:1px solid #888888;\" onclick=\"as_add_time();\">Дa</button>";
    echo "</td>";
    echo "<td valign=\"middle\" align=\"right\" width = 250>";
      echo "<button style=\"font-size: 100%; width:130px; height:25px; background-color:#f79398; border:1px solid #888888;\" onclick=\"as_delay();\">Нет</button>";
    echo "</td>";
  echo "</tr>";
echo "</table>"; 

$_SESSION['ss_delay_show_save'] = 0;
?>