<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$itemId = $_POST['itemId'];

include_once __DIR__ . "/../php_tori/connect.php";

$query = mysqli_query($link, "DELETE FROM ADD_TIME WHERE ID = '$itemId'");
$merr = mysqli_error($link);

if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  echo "1";
}
?>