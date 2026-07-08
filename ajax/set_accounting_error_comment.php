<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if (!isset($_SESSION['ss_id'])) {
  echo "Ошибка: сессия истекла. Обновите страницу.";
  exit;
}

if (!isset($_POST['error_id']) || !isset($_POST['comment'])) {
  echo "Ошибка: не переданы данные комментария.";
  exit;
}

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

$userID = (int)$_SESSION['ss_id'];
$errorID = (int)$_POST['error_id'];
$comment = trim($_POST['comment']);

if ($errorID <= 0) {
  echo "Ошибка: некорректная запись ошибки учета.";
  exit;
}

if ($comment == "") {
  echo "Ошибка: комментарий не может быть пустым.";
  exit;
}

$userIDEsc = mysqli_real_escape_string($link, $userID);
$errorIDEsc = mysqli_real_escape_string($link, $errorID);
$commentEsc = mysqli_real_escape_string($link, $comment);

$checkQuery = mysqli_query($link, "
  SELECT ID, STATUS
  FROM accounting_errors
  WHERE ID = '$errorIDEsc'
    AND USERID = '$userIDEsc'
  LIMIT 1
");

if (!$checkQuery) {
  echo mysqli_error($link);
  exit;
}

if (mysqli_num_rows($checkQuery) == 0) {
  echo "Ошибка: запись ошибки учета не найдена.";
  exit;
}

$row = mysqli_fetch_array($checkQuery, MYSQLI_ASSOC);
$status = (int)$row["STATUS"];

if ($status == 2 || $status == 4) {
  echo "Ошибка: комментарий нельзя изменить, потому что запись уже принята или удалена.";
  exit;
}

$query = mysqli_query($link, "
  UPDATE accounting_errors
  SET USER_COMMENT = '$commentEsc',
      STATUS = 1,
      USER_REPLY_DT = NOW()
  WHERE ID = '$errorIDEsc'
    AND USERID = '$userIDEsc'
    AND STATUS IN (0, 1, 3)
  LIMIT 1
");

if (!$query) {
  echo mysqli_error($link);
  exit;
}

echo "1";
exit;
?>