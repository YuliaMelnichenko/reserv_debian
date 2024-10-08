<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$link = mysqli_connect("localhost", "tori", "toriadmin", "TORI");

mysqli_set_charset($link, "utf8");

if ($link == false) {
    echo "Ошибка: Невозможно подключиться к MySQL " . mysqli_connect_error();
}
// else {
//     echo "Соединение установлено";
// }
return $link;
// $db_selected = mysqli_select_db($link, 'TORI');
// if (!$db_selected) {
// echo "<br>Cant use intec DB: $mysql_error()<br>";
// }
?>