<?php 
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include __DIR__ . "/../php_tori/connect.php";

$userID = (int)$_SESSION['ss_id'];

$currentDate = get_current_datetime_in_timezone_str( 1, 0 );

$start_time = (string) ($_POST['add_time_part_start_dt'] ?? '');
$stop_time = (string) ($_POST['add_time_part_stop_dt'] ?? '');
$base = (int)($_POST['add_time_part_base'] ?? 0);
$desk = trim((string)($_POST['add_time_part_desk'] ?? ''));

$range = get_valid_datetime_range($start_time, $stop_time);

if ($range === null) {
  echo "Время окончания должно быть позже времени начала";
  exit;
}

if ($base <= 0) {
  echo "Не выбрано основание работы вне офиса";
  exit;
}

$start_time = $range['start'];
$stop_time = $range['stop'];

if ( isset( $_POST['byAlert'] ) AND $_POST['byAlert'] == 1 ){
  $byAlert = 1;
}
else{
  $byAlert = 0;
}
  
mysqli_set_charset($link, "utf8");

$supervisor_query = db_query($link, 'SELECT SUPERVISORID FROM GROUPS WHERE TYPE = 100 AND USERID = ? LIMIT 1', 'i', array($userID));

if (!$supervisor_query) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$row = mysqli_fetch_array($supervisor_query, MYSQLI_ASSOC);

if (!$row || (int)$row['SUPERVISORID'] <= 0) {
  echo "Не найден руководитель для согласования";
  exit;
}

$sv_ID = (int)$row["SUPERVISORID"];

if (!mysqli_begin_transaction($link)) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}
                                            
$query = db_execute(
  $link,
  "INSERT INTO ADD_TIME (ADDDATE, SUIR, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, SUPERVISORDESC, APPROVED, PAUSE_MODE, BYALERT) VALUES (?, ?, ?, ?, ?, ?, ?, '', 0, 0, ?)",
  'siissisi',
  array($currentDate, $sv_ID, $userID, $start_time, $stop_time, $base, $desk, $byAlert)
);
$merr=mysqli_error($link);

if (!$query){
  mysqli_rollback($link);
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else{
  if ( isset($_SESSION['ss_ch_delay_ID']) ){
    mysqli_set_charset($link, "utf8"); 

    $addTimeDescID = (int)$_SESSION['ss_ch_delay_ID'];

    $descDel = "Из доп. времени: ".$desk;

  $query1 = db_execute($link, 'UPDATE Delays SET explaneDesk = ? WHERE id = ?', 'si', array($descDel, $addTimeDescID));

    $merr1 = mysqli_error($link);

    if (!$query1){
      mysqli_rollback($link);
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    unset($_SESSION['ss_ch_delay_ID']);
  }

  if (!mysqli_commit($link)) {
    mysqli_rollback($link);
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  echo "1";
}
?>
