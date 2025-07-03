<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id'];

include __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

mysqli_set_charset($link, "utf8");

$date_train = $_POST['training_date'];
$start_time = $_POST['training_start_time'];
$stop_time = $_POST['training_stop_time'];

$query = mysqli_query($link, "SELECT COUNT(DISTINCT USERID) FROM gym_schedule WHERE DATE_TRAIN='$date_train' AND START_TIME='$start_time' AND STOP_TIME='$stop_time'");
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