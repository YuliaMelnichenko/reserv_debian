<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID_ = $_SESSION['ss_id']; 
$currentDate = date('Y-m-d');

$mode = 0;

if ( isset( $_POST['mode'] ) ){
  $mode = $_POST['mode'];
  $delayID = $_POST['delayId'];
  $userID = $_POST['userId'];
}

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

mysqli_set_charset($link, "utf8");

if ( $mode == 0 ){
  $query0 = mysqli_query($link, "SELECT id, status, supervisorID, explaneDesk FROM Delays WHERE date = '$currentDate' AND userID = '$userID_'"); 
}
else{
  $query0 = mysqli_query($link, "SELECT status, supervisorID, explaneDesk FROM Delays WHERE id = '$delayID' AND userID = '$userID'"); 
}

$found = 0;
$status = 0;

while ( $row0 = mysqli_fetch_assoc($query0) ){
  $status = $row0["status"];
  $supervisorID = $row0["supervisorID"];
  $explaneDesk = $row0["explaneDesk"];
  $found = 1;
}

if ( $status != 0 ){
  $disableStr = "disabled";
}

echo "<h5 class=\"big\">Внесение сведений по опозданию</h5><br><br>";

$superUsers = get_superuser_names_by_user_id( $userID_ );

echo "<table cellpadding=\"0\" cellspacing=\"0\" border=1 bordercolor=\"#888888\">";
  echo "<tr>";
    echo "<td valign=\"middle\" align=\"center\" width = 200>"."<h5 class=\"middle\">С кем предварительно сограсовано"."</h5></td>";
    echo "<td valign=\"middle\" align=\"center\" width = 280>"."<h5 class=\"middle\">Объяснение работника"."</h5></td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td valign=\"middle\" align=\"center\" width = 250>";
      echo "<select $disableStr id=\"delayExplanationSU\" bgcolor=\"#888888\" width = 250 >";
        echo "<option style = \"background-color: #FFAAAA;\" value=\"-1\">Ни с кем!</option>";
        foreach( $superUsers as $superUser ){
          if ( $supervisorID == $superUser[1] ){
            echo "<option selected value=\"$superUser[1]\">$superUser[0]</option>";
          }
          else{
            echo "<option value=\"$superUser[1]\">$superUser[0]</option>";
          }
        }
      echo "</select>";      
    echo "</td>";
    echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">";
     echo "<textarea $disableStr id=\"delayExplanation\" style=\"width:240px; resize: none;\" cols=\"33\" rows=\"2\">".$explaneDesk."</textarea>";
    echo "</td>";
  echo "</tr>";
echo "</table>"; 
echo "<br>";
echo "<table>";  
echo "<tr height = 1px><td></td><td></td></td></tr>";

echo "<tr>";

if ( $status != 0 ){
  if ( $status == -1 ){
    $statusStr = "<h5 class=\"bigred\">ОТКЛОНЕНО</h5>";
  }
  else if ( $status == 1 ){
    $statusStr = "<h5 class=\"biggreen\">ПРИНЯТО</h5>";
  }
  else{
    $statusStr = "<h5 class=\"big\">НА РАССМОТРЕНИИ</h5>";
  }
  echo "<h5 id=\"explAddInfo\"class=\"small1\">** Обяснение к опозданию </h5>$statusStr<h5 class=\"small1\">. Исправление невозможно"."</h5>";
}

echo "<td bordercolor=\"#000000\" width=\"210px\" valign=\"middle\" align=\"left\">";
echo "<button style=\"font-size: 100%; width:178px; height:20px; background-color:#ff7979; border:1px solid #888888;\" onclick=\"close_explanation( '$mode' );\">Закрыть</button><br>";
echo "</td>";

echo "<td bordercolor=\"#000000\" width=\"300px\" valign=\"middle\" align=\"right\">";

if ( $mode == 0 ){
  echo "<button $disableStr style=\"font-size: 100%; width:178px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"set_explanation( '0', '-1' );\">Сохранить</button><br>";
}
else{
  echo "<button $disableStr style=\"font-size: 100%; width:178px; height:20px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"set_explanation( '$mode', '$delayID' );\">Сохранить</button><br>";
}
echo "</td>";

echo "</tr>";
echo "</table>";

?>