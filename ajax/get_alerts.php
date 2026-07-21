<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";

$userID_ = (int)$_SESSION['ss_id'];

echo "<table id = \"alert_approvement_table_users\" class=\"slim\" border=1>";
echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";
echo "<td width=68 class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Дата</h5>"."</td>";
echo "<td width=500 class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Сообщение</h5>"."</td>";
echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">"."<h5 class=\"big\">Управление</h5>"."</td>";
echo "</tr>";

$colorMode = 1;
$color1 = "#ddffff";
$color2 = "#ddeedd";
$color3 = "#ffffff";

function get_postfix( $newType, &$startMode, &$messStr ){
  if ( $newType == 11 OR $newType == 12 ){ $startMode = 0; $messStr = "Забыл отметить время ухода на обед"; return "не отмечено время ухода на обед. Рабочее время не учтено!"; }
  if ( $newType == 13 ){ $startMode = 1; $messStr = "Забыл отметить время прихода с обеда"; return "не отмечено время прихода с обеда. Учтено время от начала рабочего дня до начала обеда!"; }
  if ( $newType == 14 ){ $startMode = 2; $messStr = "Забыл отметить время ухода с рабочего места"; return "не отмечено время ухода с рабочего места. Учтено время от начала рабочего дня до начала обеда!"; } 
}

$stats = $_SESSION['ss_fails_stat'];

$img = "go1.png";

for( $idx = 0; $idx < count( $stats[0] ); $idx ++ ){
  $date = $stats[0][$idx];

  $type = $stats[8][$idx];
  $eatStart = $stats[5][$idx];
  $eatStop = $stats[6][$idx];

  $errMsg = "";
  $prefix = "";
  $postfix = "";
  $startMode = 0;
  $Sttime = ""; 

  if ( $type >= 200 ){
    $newType = $type - 200;
    if ( $newType == 10 ){ continue; } 
    $prefix = "Внеочередно рабочий день";
    $postfix = get_postfix( $newType, $startMode, $messStr );
    if ( $startMode == 0 ){ $Sttime = "10:00:00"; }
    else if ( $startMode == 1 ){ $Sttime = $eatStart; }
    else if ( $startMode == 2 ){ $Sttime = $eatStop; }  
  }
  else if ( $type != 100 ){
    if ( isWeekEnd( $date ) == 0 ){
      if ( $type == "NDF" ){
        $prefix = "Рабочий день";
        $postfix = "нет сведений";
        $messStr = "Забыл отметить время прихода на рабочее место";
        $Sttime = "10:00:00";
      }
      else{
        $newType = $type;
        if ( $newType == 10 ){ continue; }
        $prefix = "Рабочий день";
        $postfix = get_postfix( $newType, $startMode, $messStr );
        if ( $startMode == 0 ){ $Sttime = "10:00:00"; }
        else if ( $startMode == 1 ){ $Sttime = $eatStart; }
        else if ( $startMode == 2 ){ $Sttime = $eatStop; }  
      }
    }
    else{ continue; }
  }  
  else{ continue; }

  if ( $colorMode == 0 ){
    $color = $color1;
    $colorMode = 1;
  }
  else{
    $color = $color3;
    $colorMode = 0;
  }

  if ( is_there_add_time_by_alert( $date, $userID_ ) == 1 ){
    continue;
  }

  $Sttime = substr( $Sttime, 0 , 5 );

  echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
  echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"left\">"."<h5 class=\"middle\">$date</h5>"."</td>";
  echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"left\">"."<h5 class=\"middle\">$prefix: $postfix</h5>"."</td>";
  echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">";
    echo "<button id = \"explBtn\" class=\"journal-cell-icon-button\" title = \"Просмотреть\" onclick=\"fill_alerts_by_user( '$userID_', '$date', '$Sttime', '$messStr' );\"><img src=\"img/$img\"></button>";
  echo "</td>";
  echo "</tr>";
}

include __DIR__ . "/../php_tori/connect.php";

$currentDate = date('Y-m-d');           

mysqli_set_charset($link, "utf8");
$query = db_query($link, "SELECT * FROM ALERTS WHERE DATE = ? AND USERID = ? AND VIEWED = 0", 'si', array($currentDate, $userID_));

$merr=mysqli_error($link);
if ( !$query ){
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else{
  while ( $row = mysqli_fetch_assoc($query) ){
    $date = $row["DATE"];
    $id = (int)$row["ID"];
    $comments = $row["COMMENT"];

    echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
    echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"left\">"."<h5 class=\"middle\">$date</h5>"."</td>";
    echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"left\"><h5 class=\"middle\">" . html_escape($comments) . "</h5></td>";
    echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">";
      echo "<button id=\"explBtn\" class=\"journal-cell-icon-button\" title=\"Отметить как просмотренное\" onclick=\"set_alert_viewed($id);\"><img src=\"img/closeSmall.png\" alt=\"\"></button>";
    echo "</td>";
    echo "</tr>";
  }
}

echo "</table>";
?>
