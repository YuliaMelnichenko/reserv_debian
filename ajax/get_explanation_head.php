<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

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
      echo "<button class=\"delay-question-button delay-question-button-yes\" onclick=\"as_add_time();\">Дa</button>";
    echo "</td>";
    echo "<td valign=\"middle\" align=\"right\" width = 250>";
      echo "<button class=\"delay-question-button delay-question-button-no\" onclick=\"as_delay();\">Нет</button>";
    echo "</td>";
  echo "</tr>";
echo "</table>"; 

$_SESSION['ss_delay_show_save'] = 0;
?>
