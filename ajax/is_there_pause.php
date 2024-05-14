<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$userID = $_SESSION['ss_id']; 
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];

include_once "/var/www/tori/php_tori/connect.php";

$query = mysqli_query($link, "select take_pause from visiting where id = '$ss_visiting_ID' and user_id = '$userID'");
$merr=mysqli_error($link);
if (!$query)
{
  echo "<br>mysql_error = $merr<br>";
}
else
{
  $vn=mysqli_num_rows($query);
  if ( $vn == 0 )
  {
    echo "0";
  } 
  else
  {
    if ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {  
      $take_pause = $row["take_pause"];
      echo $take_pause;
    }
  }  
}
?>