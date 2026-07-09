<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if (!isset($_SESSION['ss_id'])) {
  echo "Ошибка: пользователь не найден";
  exit;
}

if (!isset($_POST['visit_id']) || !isset($_POST['add_stop_eat_time'])) {
  echo "Ошибка: не переданы данные для изменения времени";
  exit;
}

$userID = (int)$_SESSION['ss_id'];
$visitID = (int)$_POST['visit_id'];
$newStopEatTimeRaw = (string)$_POST['add_stop_eat_time'];

if ($visitID <= 0) {
  echo "Ошибка: некорректная запись посещения";
  exit;
}

$newStopEatTime = str_replace('T', ' ', $newStopEatTimeRaw);

if (strlen($newStopEatTime) == 16) {
  $newStopEatTime .= ":00";
}

if (strtotime($newStopEatTime) === false) {
  echo "Ошибка: некорректная дата прихода с обеда";
  exit;
}

include_once __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

mysqli_set_charset($link, "utf8");

$query = db_query($link, "
  SELECT ID, in_dt, eat_start_dt, eat_stop_dt
  FROM visiting
  WHERE ID = ?
    AND user_id = ?
  LIMIT 1
", 'ii', array($visitID, $userID));

if (!$query) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

if (mysqli_num_rows($query) == 0) {
  echo "Ошибка: запись посещения не найдена";
  exit;
}

$row = mysqli_fetch_array($query, MYSQLI_ASSOC);
$eatStartDT = $row["eat_start_dt"];
$eatStopDT = $row["eat_stop_dt"];

if ($eatStartDT == "0000-00-00 00:00:00" || strtotime($eatStartDT) === false) {
  echo "Ошибка: время ухода на обед не найдено";
  exit;
}

if ($eatStopDT != "0000-00-00 00:00:00" && $eatStopDT != "" && $eatStopDT !== null) {
  echo "Ошибка: время прихода с обеда уже заполнено";
  exit;
}

if (strtotime($newStopEatTime) <= strtotime($eatStartDT)) {
  echo "Ошибка: время прихода с обеда должно быть больше времени ухода на обед";
  exit;
}

$currentStartDT = isset($_SESSION['ss_startDTStr'])
  ? $_SESSION['ss_startDTStr']
  : date('Y-m-d 00:00:00');

if (strtotime($newStopEatTime) >= strtotime($currentStartDT)) {
  echo "Ошибка: время прихода с обеда за предыдущий день не должно попадать в текущий рабочий период";
  exit;
}

$res = db_execute($link, "
  UPDATE visiting
  SET eat_stop_dt = ?,
      state = 4,
      changes = 1
  WHERE ID = ?
    AND user_id = ?
", 'sii', array($newStopEatTime, $visitID, $userID));

if (!$res) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$logText = "Ручное изменение времени прихода с обеда. visiting.ID=$visitID; eat_stop_dt=$newStopEatTime";

db_execute($link, "
  INSERT INTO logging_changes (USER_ID, DATE_CHANGE, CHANGES)
  VALUES (?, NOW(), ?)
", 'is', array($userID, $logText));

echo "2";
?>
