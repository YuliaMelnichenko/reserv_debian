<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if (!isset($_SESSION['ss_id'])) {
  echo "Ошибка: сессия истекла. Обновите страницу.";
  exit;
}

if (!isset($_POST['error_id']) || !isset($_POST['status'])) {
  echo "Ошибка: не переданы данные решения.";
  exit;
}

include_once __DIR__ . "/../funcs.php";
include __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

$supervisorID = (int)$_SESSION['ss_id'];
$errorID = (int)$_POST['error_id'];
$status = (int)$_POST['status'];
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : "";

if (am_i_superuser($supervisorID) != 1) {
  echo "Ошибка: недостаточно прав.";
  exit;
}

if ($errorID <= 0) {
  echo "Ошибка: некорректная запись ошибки учета.";
  exit;
}

if ($status != 2 && $status != 3 && $status != 4) {
  echo "Ошибка: некорректный статус решения.";
  exit;
}

$supervisorIDEsc = mysqli_real_escape_string($link, $supervisorID);
$errorIDEsc = mysqli_real_escape_string($link, $errorID);

$checkQuery = mysqli_query($link, "
  SELECT ae.ID, ae.USERID, ae.STATUS
  FROM accounting_errors ae
  INNER JOIN GROUPS g ON g.USERID = ae.USERID
  WHERE ae.ID = '$errorIDEsc'
    AND g.SUPERVISORID = '$supervisorIDEsc'
    AND g.TYPE = 3
  LIMIT 1
");

if (!$checkQuery) {
  echo mysqli_error($link);
  exit;
}

if (mysqli_num_rows($checkQuery) == 0) {
  echo "Ошибка: запись ошибки учета не найдена или недоступна.";
  exit;
}

$row = mysqli_fetch_array($checkQuery, MYSQLI_ASSOC);
$currentStatus = (int)$row["STATUS"];

if ($currentStatus == 4 && $status != 4) {
  echo "Ошибка: удаленную запись нельзя изменить.";
  exit;
}

$commentEsc = mysqli_real_escape_string($link, $comment);
$statusEsc = mysqli_real_escape_string($link, $status);

$query = mysqli_query($link, "
  UPDATE accounting_errors
  SET STATUS = '$statusEsc',
      SUPERVISORID = '$supervisorIDEsc',
      SUPERVISOR_COMMENT = '$commentEsc',
      SUPERVISOR_REPLY_DT = NOW()
  WHERE ID = '$errorIDEsc'
  LIMIT 1
");

if (!$query) {
  echo mysqli_error($link);
  exit;
}

echo "1";
exit;
?>