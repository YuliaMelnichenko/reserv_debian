<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if (!isset($_SESSION['ss_id'])) {
  echo "Ошибка: пользователь не найден";
  exit;
}

$userID = $_SESSION['ss_id'];

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8");

$currentStartDT = isset($_SESSION['ss_startDTStr'])
  ? $_SESSION['ss_startDTStr']
  : date('Y-m-d 00:00:00');

$query = mysqli_query($link, "
  SELECT ID, in_dt, eat_start_dt, eat_stop_dt, out_dt, state
  FROM visiting
  WHERE user_id = '$userID'
    AND state != 0
    AND in_dt < '$currentStartDT'
  ORDER BY in_dt DESC, ID DESC
  LIMIT 1
");

if (!$query) {
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
  exit;
}

if (mysqli_num_rows($query) == 0) {
  echo "<div class=\"reg_out_time\">";
  echo "<div class=\"reg_out_time_head\">";
  echo "<div class=\"reg_out_time_text\"><h5 class=\"big\">Незакрытых предыдущих дней не найдено</h5></div>";
  echo "<div class=\"reg_out_time_close\"><img onclick=\"close_out_time();\" src=\"img/closeSmall.png\"></div>";
  echo "</div>";
  echo "<div class=\"reg_out_time_footer\">";
  echo "<button class=\"reg_out_time_button_close\" onclick=\"close_out_time();\">Закрыть</button>";
  echo "</div>";
  echo "</div>";
  exit;
}

$row = mysqli_fetch_array($query, MYSQLI_ASSOC);

$visitID = (int)$row["ID"];
$inDT = $row["in_dt"];
$state = (int)$row["state"];

$minValue = date('Y-m-d\TH:i', strtotime($inDT));
$maxValue = date('Y-m-d\TH:i', strtotime($currentStartDT . ' -1 second'));

$defaultValue = date('Y-m-d\T19:00', strtotime($inDT));

if (strtotime(str_replace('T', ' ', $defaultValue)) <= strtotime($inDT)) {
  $defaultValue = date('Y-m-d\TH:i', strtotime($inDT . ' +8 hours'));
}

if (strtotime(str_replace('T', ' ', $defaultValue)) > strtotime(str_replace('T', ' ', $maxValue))) {
  $defaultValue = $maxValue;
}

echo "<div class=\"reg_out_time\">";

  echo "<div class=\"reg_out_time_head\">";
    echo "<div class=\"reg_out_time_text\">";
      echo "<h5 class=\"big\">Введите дату и время ухода!</h5>";
    echo "</div>";
    echo "<div class=\"reg_out_time_close\">";
      echo "<img onclick=\"close_out_time();\" src=\"img/closeSmall.png\">";
    echo "</div>";
  echo "</div>";

  echo "<div class=\"reg_out_time_body\">";
    echo "<h5 class=\"middle\">Незакрытый приход: " . htmlspecialchars($inDT) . "</h5>";
    echo "<input id=\"change_visit_id\" type=\"hidden\" value=\"$visitID\">";
    echo "<input id=\"add_stop_time\" align=\"middle\" style=\"width:175px;\" type=\"datetime-local\" value=\"$defaultValue\" min=\"$minValue\" max=\"$maxValue\">";
  echo "</div>";

  echo "<div class=\"reg_out_time_footer\">";
    echo "<div class=\"reg_out_time_save\">";
      echo "<button class=\"reg_out_time_button_save\" onclick=\"save_changes_time($userID);\">Сохранить</button>";
    echo "</div>";
    echo "<div class=\"reg_out_time_close\">";
      echo "<button class=\"reg_out_time_button_close\" onclick=\"close_out_time();\">Отмена</button>";
    echo "</div>";
  echo "</div>";

echo "</div>";
?>