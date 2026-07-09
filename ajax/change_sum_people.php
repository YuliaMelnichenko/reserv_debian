<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include __DIR__ . "/../php_tori/connect.php";

$date_train = (string) ($_POST['training_date'] ?? '');
$start_time = (string) ($_POST['training_start_time'] ?? '');
$stop_time = (string) ($_POST['training_stop_time'] ?? '');

$query = db_query(
  $link,
  'SELECT COUNT(DISTINCT USERID) AS people_count FROM gym_schedule WHERE DATE_TRAIN = ? AND START_TIME = ? AND STOP_TIME = ?',
  'sss',
  array($date_train, $start_time, $stop_time)
);

if (!$query) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$row = mysqli_fetch_assoc($query);
$count = (int)$row['people_count'];

if ($count >= 4) {
  echo "1";
}
else {
  echo "2";
}
?>
