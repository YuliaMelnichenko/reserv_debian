<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
              
$delID = (int) ($_POST['addID'] ?? 0);
require_ajax_add_time_access($delID);

include_once __DIR__ . "/../php_tori/connect.php";

$query = db_execute($link, 'DELETE FROM ADD_TIME WHERE ID = ?', 'i', array($delID));

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo "<br>mysql_error = $merr<br>";
} 
echo $delID;                         
?>
