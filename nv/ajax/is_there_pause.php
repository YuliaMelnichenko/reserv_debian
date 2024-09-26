<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$userID = $_SESSION['ss_id']; 
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];

include_once"../../php_tori/connect.php";

$query = mysql_query("select take_pause from visiting where id = '$ss_visiting_ID' and user_id = '$userID'");
$merr=mysql_error();
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  $vn=mysql_num_rows($query);
  if ( $vn == 0 )
  {
    echo "0";
  } 
  else
  {
    if ( $row = mysql_fetch_array($query, MYSQL_ASSOC) )
    {  
      $take_pause = $row["take_pause"];
      echo $take_pause;
    }
  }  
}
?>


                                                                         