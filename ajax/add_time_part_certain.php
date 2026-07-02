<?php 
session_start();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include __DIR__ . "/../php_tori/connect.php";

$userID = $_SESSION['ss_id']; 

$currentDate = get_current_datetime_in_timezone_str( 1, 0 );

$start_time = $_POST['add_time_part_start_dt'];
$stop_time = $_POST['add_time_part_stop_dt'];
$base = $_POST['add_time_part_base'];
$desk = $_POST['add_time_part_desk'];

if ( isset( $_POST['byAlert'] ) AND $_POST['byAlert'] == 1 ){
  $byAlert = 1;
}
else{
  $byAlert = 0;
}
  
mysqli_set_charset($link, "utf8");

$supervisor_query = mysqli_query($link,"SELECT SUPERVISORID FROM GROUPS WHERE TYPE = 100 AND USERID = '$userID'");
$row = mysqli_fetch_array($supervisor_query);

$sv_ID = $row["SUPERVISORID"];
                                            
$query = mysqli_query($link, "INSERT INTO ADD_TIME (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT ) VALUES ('$currentDate', '$sv_ID', '$userID','$start_time','$stop_time','$base','$desk', '', '0', '0', '$byAlert')");
$merr=mysqli_error($link);

if (!$query){
  echo "<br>mysql_error = $merr<br>";
}
else{
  if ( isset($_SESSION['ss_ch_delay_ID']) ){
    mysqli_set_charset($link, "utf8"); 

    $addTimeDescID = $_SESSION['ss_ch_delay_ID'];

    $descDel = "<font color=\"#FF0000\">Из доп. времени:</font> ".$desk;

    $query1 = mysqli_query($link, "UPDATE Delays SET explaneDesk = '$descDel' WHERE id = '$addTimeDescID'");

    unset($_SESSION['ss_ch_delay_ID']);
                                        
    $merr1 = mysqli_error($link);

    if (!$query1){
      echo "<br>mysql_error = $merr<br>";
    }    
    else{
      echo "1";
    }
  }
  else{
    echo "1";
  }
}
?>