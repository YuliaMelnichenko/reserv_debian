<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_superuser();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$ID = (int) ($_POST['recID'] ?? 0);
require_ajax_add_time_access($ID);

include_once __DIR__ . "/../php_tori/connect.php";

$query = db_execute($link, 'UPDATE ADD_TIME SET APPROVED = -1 WHERE ID = ?', 'i', array($ID));

$merr=mysqli_error($link);
if ( !$query ) 
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
} 
echo $ID;                         
?>
