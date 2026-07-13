<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = (int)$_SESSION['ss_id'];

include_once __DIR__ . "/../funcs.php";
include __DIR__ . "/../php_tori/connect.php";

echo "<table class=\"pause-dialog-table\">";
  echo "<tr>";
    echo "<td class=\"pause-dialog-close-cell\">";
      echo "<button class=\"pause-dialog-close\" title=\"Закрыть\" onclick=\"close_pause();\"><img src=\"img/closeSmall.png\" alt=\"\"></button>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"pause-dialog-cell\">";
      echo "<h5 class=\"big\">с кем согласовано</h5>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"pause-dialog-cell\">";
      $SUsers = get_pause_agree_able_superusers_by_userID( $userID );
      echo "<select id=\"pause_superusers\" class=\"pause-dialog-select\">";
      foreach( $SUsers as $SUser )
      { 
        echo "<option value=\"" . (int)$SUser[0] . "\">" . html_escape($SUser[1]) . "</option>";
      }
      echo "</select>";      
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"pause-dialog-cell\">";
      echo "<h5 class=\"big\"><br>комментарий</h5>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"pause-dialog-cell\">";
      echo "<textarea id=\"pause_desk\" class=\"pause-dialog-textarea\" cols=\"43\" rows=\"3\"></textarea>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"pause-dialog-cell\">";
      echo "<br><button class=\"pause-dialog-action\" onclick=\"set_pause_state();\">Приостановка учета времени</button>";
    echo "</td>";
  echo "</tr>";
echo "</table>"; 
?>
