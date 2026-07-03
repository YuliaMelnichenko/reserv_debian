<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id'];

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

mysqli_set_charset($link, "utf8");

$date_train = (string) ($_POST['training_date'] ?? '');
$start_time = (string) ($_POST['training_start_time'] ?? '');
$stop_time = (string) ($_POST['training_stop_time'] ?? '');

$query = db_query(
  $link,
  'SELECT COUNT(DISTINCT USERID) FROM gym_schedule WHERE DATE_TRAIN = ? AND START_TIME = ? AND STOP_TIME = ?',
  'sss',
  array($date_train, $start_time, $stop_time)
);
$row = mysqli_fetch_assoc($query);
$merr = mysqli_error($link);

$count = $row["COUNT(DISTINCT USERID)"];

if (!$query) {
    $err .= "mysql_error $merr<br>";
}
else {
    $newID = $newID + 1;
}

if ($count > '3' && $count < '5') {
    echo "1";
}
else {
    echo "2";
}
?>
