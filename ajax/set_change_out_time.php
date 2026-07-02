<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if (!isset($_SESSION['ss_id'])) {
  echo "Ошибка: пользователь не найден";
  exit;
}

if (!isset($_POST['visit_id']) || !isset($_POST['add_stop_time'])) {
  echo "Ошибка: не переданы данные для изменения времени";
  exit;
}

$userID = $_SESSION['ss_id'];
$visitID = (int)$_POST['visit_id'];
$newOutTimeRaw = $_POST['add_stop_time'];

if ($visitID <= 0) {
  echo "Ошибка: некорректная запись посещения";
  exit;
}

$newOutTime = str_replace('T', ' ', $newOutTimeRaw);

if (strlen($newOutTime) == 16) {
  $newOutTime .= ":00";
}

if (strtotime($newOutTime) === false) {
  echo "Ошибка: некорректная дата ухода";
  exit;
}

include_once __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

mysqli_set_charset($link, "utf8");

$userID = mysqli_real_escape_string($link, $userID);
$newOutTime = mysqli_real_escape_string($link, $newOutTime);

$query = mysqli_query($link, "
  SELECT ID, in_dt, state
  FROM visiting
  WHERE ID = '$visitID'
    AND user_id = '$userID'
  LIMIT 1
");

if (!$query) {
  echo "Ошибка БД: " . mysqli_error($link);
  exit;
}

if (mysqli_num_rows($query) == 0) {
  echo "Ошибка: запись посещения не найдена";
  exit;
}

$row = mysqli_fetch_array($query, MYSQLI_ASSOC);
$inDT = $row["in_dt"];

if (strtotime($newOutTime) <= strtotime($inDT)) {
  echo "Ошибка: время ухода должно быть больше времени прихода";
  exit;
}

$currentStartDT = isset($_SESSION['ss_startDTStr'])
  ? $_SESSION['ss_startDTStr']
  : date('Y-m-d 00:00:00');

if (strtotime($newOutTime) >= strtotime($currentStartDT)) {
  echo "Ошибка: время ухода за предыдущий день не должно попадать в текущий рабочий период";
  exit;
}

$res = mysqli_query($link, "
  UPDATE visiting
  SET out_dt = '$newOutTime',
      state = 0,
      changes = 1
  WHERE ID = '$visitID'
    AND user_id = '$userID'
");

if (!$res) {
  echo "Ошибка БД: " . mysqli_error($link);
  exit;
}

$logText = mysqli_real_escape_string($link, "Ручное изменение времени ухода. visiting.ID=$visitID; out_dt=$newOutTime");

mysqli_query($link, "
  INSERT INTO logging_changes (USER_ID, DATE_CHANGE, CHANGES)
  VALUES ('$userID', NOW(), '$logText')
");

echo "2";
?>