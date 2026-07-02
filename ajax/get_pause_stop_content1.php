<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id']; 
$currentDate = date('Y-m-d');

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

mysqli_set_charset($link, "utf8"); 

error_reporting(E_ALL | E_STRICT) ;
ini_set('display_errors', 'On');

$query = mysqli_query($link, "SELECT ID, SUIR, STARTTIME, DESCRIPTION FROM ADD_TIME WHERE STARTDATE = '$currentDate' AND USERID = '$userID' AND PAUSE_MODE = 1 )");

echo "$currentDate";

?>