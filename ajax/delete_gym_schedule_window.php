<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

$userID = $_SESSION['ss_id'];

mysqli_set_charset($link, "utf8");

$query = mysqli_query($link, "SELECT * FROM gym_schedule WHERE USERID = '$userID' ORDER BY DATE_TRAIN");

$content = "<div id=\"delete_schedule\">";
  $content .= "<div class=\"schedule_text\">";
  $content .= "<h5>Выберите расписание для удаления.</h5>";
  $content .= "</div>";
  $content .= "<div id=\"schedule_box\">";

while($row = mysqli_fetch_assoc($query)){
    $userId = $row["USERID"];
    $date_train = $row['DATE_TRAIN'];
    $start_time = $row["START_TIME"];
    $stop_time = $row["STOP_TIME"];
        
    $content .= "<div id=\"textarea_schedule\">";
    $content .= "<textarea class=\"schedule\" cols=\"33\" rows=\"2\">$date_train "." $start_time "." $stop_time</textarea>";
    $content .= "<button id=\"delete_button_schd\" onclick=\"delete_gym_schedule('$date_train', '$start_time', '$stop_time');\"><img src=\"img/delete_small.bmp\"></button>";
    $content .= "</div>";
}
  $content .= "</div>";
$content .= "</div>";


$content .= "<table cellpadding=\"0\" cellspacing=\"0\" border=0 width=350>";  
$content .= "<tr>";
$content .= "<td bordercolor=\"#000000\" width=\"100\" valign=\"middle\" align=\"right\">";
$content .= "<button class=\"gym-close-button\" onclick=\"close_add_sport_time();\">Закрыть</button><br>";
$content .= "</td>";

$content .= "</tr>";
$content .= "</table><br>";  

echo $content;
?>
