<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID_ = $_SESSION['ss_id'];   

$ta_start_date_time = (string) ($_POST['ta_start_date_time'] ?? '');
$ta_stop_date_time = (string) ($_POST['ta_stop_date_time'] ?? '');
$ta_base = (string) ($_POST['ta_base'] ?? '');
$ta_desc = (string) ($_POST['ta_desc'] ?? '');


$ta_start_date = substr( $ta_start_date_time, 0, 10 );
$ta_stop_date = substr( $ta_stop_date_time, 0, 10 );
$ta_start_time = substr( $ta_start_date_time, 11, 8 );
$ta_stop_time = substr( $ta_stop_date_time, 11, 8 );

include_once __DIR__ . "/../php_tori/connect.php";

$query0 = mysqli_query($link, "SELECT * FROM ADD_TIME"); 

$newID = 0;

$merr=mysqli_error($link);
if ( !$query0 ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else
{
  $vn=mysqli_num_rows($query0);
  $newID = $vn + 1;
}

mysqli_set_charset($link, "utf8");
$query = db_execute($link, 'INSERT INTO ADD_TIME (ID, USERID, STARTDATE, STOPDATE, STARTTIME, STOPTIME, REASON, DESCRIPTION, APPROVED) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)', 'iissssss', array($newID, $userID_, $ta_start_date, $ta_stop_date, $ta_start_time, $ta_stop_time, $ta_base, $ta_desc));
$merr=mysqli_error($link);
if (!$query)
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
?>
