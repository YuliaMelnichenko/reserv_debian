<?php
ob_start();
session_start();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>                                                                                                                   
<head>
<title>Регистрация сотрудника</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META NAME="Author" CONTENT="InTec">
<link rel="stylesheet" type="text/css" href="style/style.css" />
</head>
<body bgcolor="#ffffff">
<div align="left">

<?php
$err = array(); 
	
include_once __DIR__ . "/php_tori/connect.php";

$user_id = $_SESSION['ss_id'];

if (isset( $_POST['_submit_state']) )    
{
  if ( $_POST['_submit_state'] == 1 )
    $query = mysqli_query($link, "UPDATE employees SET STATE = 2 WHERE id='$user_id'");  
  if ( $_POST['_submit_state'] == 2 )
    $query = mysqli_query($link, "UPDATE employees SET STATE = 1 WHERE id='$user_id'"); 

  header("Location: index.php");
    exit(); 
}
?>

</div>
</body>
</html> 