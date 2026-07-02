<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID_ = $_SESSION['ss_id']; 

$stats = $_SESSION['ss_fails_stat'];

$img = "go1.png";

for( $idx = 0; $idx < count( $stats[0] ); $idx ++ )
{
  $date = $stats[0][$idx];

  $type = $stats[8][$idx];
  $eatStart = $stats[5][$idx];
  $eatStop = $stats[6][$idx];

  $errMsg = "";
  $prefix = "";
  $postfix = "";
  $startMode = 0;
  $Sttime = ""; 

  if ( $type >= 200 )
  {
    $newType = $type - 200;
    if ( $newType == 10 ){ continue; } 
    $prefix = "Внеочередно рабочий день";
    if ( $startMode == 0 ){ $Sttime = "10:00:00"; }
    else if ( $startMode == 1 ){ $Sttime = $eatStart; }
    else if ( $startMode == 2 ){ $Sttime = $eatStop; }  
  }
  else if ( $type != 100 )
  {
    if ( isWeekEnd( $date ) == 0 )
    {
      if ( $type == "NDF" )
      { 
        $prefix = "Рабочий день";
        $postfix = "нет сведений";
        $messStr = "Забыл отметить время прихода на рабочее место";
        $Sttime = "10:00:00";
      }
      else
      {
        $newType = $type;
        if ( $newType == 10 ){ continue; }
        $prefix = "Рабочий день";
        if ( $startMode == 0 ){ $Sttime = "10:00:00"; }
        else if ( $startMode == 1 ){ $Sttime = $eatStart; }
        else if ( $startMode == 2 ){ $Sttime = $eatStop; }  
      }
    }
    else{ continue; }
  }  
  else{ continue; }

  if ( $colorMode == 0 )
  {
    $color = $color1;
    $colorMode = 1;
  }
  else
  {
    $color = $color3;
    $colorMode = 0;
  }

  if ( is_there_add_time_by_alert( $date, $userID_ ) == 1 )
  {
    continue;
  }

  echo "1";
  return;
}

if ( is_there_additional_alerts( $userID_ ) == 1 )
{
  echo "1";
  return;
}

echo "0";
return;
?>