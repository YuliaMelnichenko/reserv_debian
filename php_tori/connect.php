<?php

// $link = mysqli_connect("localhost", "tori", "toriadmin");

// if ($link == false) {
//     echo("Ошибка: Невозможно подключиться к MySQL " . mysqli_connect_error());
// }
// else {
//     echo("Соединение установлено");
// }

$mysqli = new mysqli("localhost", "tori", "toriadmin") or die ("Could not connect to database");

$db_selected = $mysqli -> select_db('TORI' , $mysqli);
if (!$db_selected) {
echo "<br>Cant use intec DB: $mysql_error()<br>";
}
?>