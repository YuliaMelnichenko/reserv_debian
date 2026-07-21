<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if (!isset($_SESSION['ss_id'])) {
  echo "Ошибка: пользователь не найден";
  exit;
}

if (!request_post_has('visit_id') || !request_post_has('add_stop_time')) {
  echo "Ошибка: не переданы данные для изменения времени";
  exit;
}

$userID = (int)$_SESSION['ss_id'];
$visitID = request_post_int('visit_id');
$newOutTimeRaw = request_post_string('add_stop_time');

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

$query = db_query($link, "
  SELECT ID, in_dt, state
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

$res = db_execute($link, "
  UPDATE visiting
  SET out_dt = ?,
      state = 0,
      changes = 1
  WHERE ID = ?
    AND user_id = ?
", 'sii', array($newOutTime, $visitID, $userID));

if (!$res) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

$logText = "Ручное изменение времени ухода. visiting.ID=$visitID; out_dt=$newOutTime";

db_execute($link, "
  INSERT INTO logging_changes (USER_ID, DATE_CHANGE, CHANGES)
  VALUES (?, NOW(), ?)
", 'is', array($userID, $logText));

echo "2";
?>
