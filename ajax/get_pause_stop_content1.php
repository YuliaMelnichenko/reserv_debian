<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$userID = $_SESSION['ss_id']; 
$currentDate = date('Y-m-d');

include_once "/var/www/tori/funcs.php";
include_once "/var/www/tori/php_tori/connect.php";

mysqli_set_charset($link, "utf8"); 

error_reporting(E_ALL | E_STRICT) ;
ini_set('display_errors', 'On');

$query = mysqli_query($link, "select ID, SUIR, STARTTIME, DESCRIPTION from ADD_TIME where STARTDATE = '$currentDate' and USERID = '$userID' and PAUSE_MODE = 1 )");

echo "$currentDate";

?>