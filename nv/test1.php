<?

$currentDate = date('Y-m-d');

$time = "10:20:30";

echo $time;

include_once "funcs.php";

$offsetTime = "03:17:24";

$time = dec_time_by_time( $time, $offsetTime );

echo "<br>".$time;

?>