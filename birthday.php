<?php 
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include "/var/www/tori/php_tori/connect.php";
include_once "/var/www/tori/funcs.php";

mysqli_set_charset($link, "utf8");

$query = mysqli_query($link, "SELECT b.user_id, DATE_FORMAT(b.day, '%d.%m') AS birthDay, e.lastname, e.firstname, e.surname FROM birthday b JOIN employees e ON b.user_id = e.id");

$row = mysqli_fetch_array($query);

$id = $row['user_id'];
$birth_day = $row['birthDay'];
$surname = $row['surname'];
$firstname = $row['firstname'];
$lastname = $row['lastname'];

$today = date("d.m");
$user_id = $_SESSION['ss_id'];

if ($birth_day === $today) {
    if ($id === $user_id) {
        echo "<div class=\"birth_person\">";
            echo "<h5>Дорогой коллега! Поздравляем тебя от всего коллектива \"ТОРИ\" с днем рождения!</h5>";
            echo "<button id=\"close_birth_win\" onclick=\"close_birth_window();\">Закрыть</button>";
        echo "</div>";
    }
    else {
        echo "<div class=\"birth_person\">";
            echo "<h5>Сегодня день рождения у ".$surname." ".$firstname." ".$lastname."</h5>";
            echo "<button id=\"close_birth_win\" onclick=\"close_birth_window();\">Закрыть</button>";
        echo "</div>";
    }
}
?>