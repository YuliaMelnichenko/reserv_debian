<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$itemId = $_POST['itemId'];

include_once"../../php_tori/connect.php";

$query = mysql_query("delete from ADD_TIME where ID = '$itemId'");
$merr=mysql_error();
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  echo "1";
}
?>


                                                                         