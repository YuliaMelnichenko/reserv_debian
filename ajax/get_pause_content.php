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

echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0 style=\"margin:0; padding:0; margin-left:0;\" >";
  echo "<tr>";
    echo "<td align=\"right\" width = \"250\">";
      echo "<img onclick=\"close_pause();\" src=\"img/closeSmall.png\">";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td style=\"margin:0; padding:0; margin-left:0;\">";
      echo "<h5 valign=\"middle\" align=\"left\" class=\"big\">с кем согласовано</h5>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td valign=\"middle\" align=\"left\" style=\"margin:0; padding:0; margin-left:0;\">";
      $SUsers = get_pause_agree_able_superusers_by_userID( $userID );
      echo "<select id=\"pause_superusers\" bgcolor=\"#888888\" style=\"width:255px; border:1px solid #888888;\" >";
      foreach( $SUsers as $SUser )
      {
        echo "<option value=\"" . (int)$SUser[0] . "\">" . html_escape($SUser[1]) . "</option>";
      }
      echo "</select>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td  style=\"margin:0; padding:0; margin-left:0;\">";
      echo "<h5 valign=\"middle\" align=\"left\" class=\"big\"><br>комментарий</h5>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td valign=\"middle\" style=\"margin:0; padding:0; margin-left:0;\" align=\"left\">";
      echo "<textarea id=\"pause_desk\" style=\"width:250px; resize: none;\" cols=\"43\" rows=\"3\">".""."</textarea>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td valign=\"middle\" align=\"left\">";
      echo "<br><button style=\"margin:0; padding:0; font-size: 100%; width:245px; height:30px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"set_pause_state();\">Приостановка учета времени</button>";
    echo "</td>";
  echo "</tr>";
echo "</table>";
?>
