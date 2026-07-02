<?php
// session_start();
include_once __DIR__ . "/start.php";
$end_time = microtime();
$end_array = explode(" ",$end_time);
$end_time = $end_array[1] + $end_array[0];
$time = $end_time - $_SESSION['genTimeStart'];
printf("Страница сгенерирована за %f секунд",$time); 
?>