<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id']; 
$ss_visiting_ID = $_SESSION['ss_visiting_ID'];

include_once __DIR__ . "/../php_tori/connect.php";

$query = mysqli_query($link, "SELECT take_pause FROM visiting WHERE id = '$ss_visiting_ID' AND user_id = '$userID'");
$merr=mysqli_error($link);
if (!$query)
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
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