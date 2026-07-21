<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = (int)$_SESSION['ss_id'];
$ss_visiting_ID = (int)$_SESSION['ss_visiting_ID'];

include_once __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

$query = db_query($link, "SELECT take_pause FROM visiting WHERE id = ? AND user_id = ?", 'ii', array($ss_visiting_ID, $userID));
$merr=mysqli_error($link);
if (!$query)
{
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
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
