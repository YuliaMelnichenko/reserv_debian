<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID_ = (int)$_SESSION['ss_id'];
$currentDate = date('Y-m-d');

$mode = 0;
$delayID = 0;
$userID = $userID_;

if ( isset( $_POST['mode'] ) ){
  $mode = (int)$_POST['mode'];
  $delayID = isset($_POST['delayId']) ? (int)$_POST['delayId'] : 0;
  $userID = isset($_POST['userId']) ? (int)$_POST['userId'] : $userID_;

  if ($mode != 0) {
    require_ajax_self_or_superuser($userID);
  }
}

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

mysqli_set_charset($link, "utf8");

if ( $mode == 0 ){
  $query0 = db_query($link, "SELECT id, status, supervisorID, explaneDesk FROM Delays WHERE date = ? AND userID = ?", 'si', array($currentDate, $userID_));
}
else{
  $query0 = db_query($link, "SELECT status, supervisorID, explaneDesk FROM Delays WHERE id = ? AND userID = ?", 'ii', array($delayID, $userID));
}

if (!$query0) {
  http_response_code(500);
  echo "Ошибка базы данных";
  exit;
}

$found = 0;
$status = 0;
$supervisorID = -1;
$explaneDesk = "";
$disableStr = "";

while ( $row0 = mysqli_fetch_assoc($query0) ){
  $status = $row0["status"];
  $supervisorID = $row0["supervisorID"];
  $explaneDesk = strip_tags($row0["explaneDesk"]);
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
     echo "<textarea $disableStr id=\"delayExplanation\" class=\"delay-explanation-textarea\" cols=\"33\" rows=\"2\">" . html_escape($explaneDesk) . "</textarea>";
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
echo "<button class=\"delay-explanation-button delay-explanation-button-close\" onclick=\"close_explanation( '$mode' );\">Закрыть</button><br>";
echo "</td>";

echo "<td bordercolor=\"#000000\" width=\"300px\" valign=\"middle\" align=\"right\">";

if ( $mode == 0 ){
  echo "<button $disableStr class=\"delay-explanation-button delay-explanation-button-save\" onclick=\"set_explanation( '0', '-1' );\">Сохранить</button><br>";
}
else{
  echo "<button $disableStr class=\"delay-explanation-button delay-explanation-button-save\" onclick=\"set_explanation( '$mode', '$delayID' );\">Сохранить</button><br>";
}
echo "</td>";

echo "</tr>";
echo "</table>";

?>
