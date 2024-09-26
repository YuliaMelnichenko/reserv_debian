<?
$link = mysql_connect("localhost", "tori", "toriadmin")
or die ("Could not connect to database");

$db_selected = mysql_select_db('TORI', $link);
if (!$db_selected) {
echo "<br>Cant use intec DB: $mysql_error()<br>";
}
?>