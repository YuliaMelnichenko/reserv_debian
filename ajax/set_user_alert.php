<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_superuser();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( isset($_POST['userID']) AND isset($_POST['messageMode']) )
{
  include_once __DIR__ . "/../funcs.php";
  include_once __DIR__ . "/../php_tori/connect.php";

  $userID = (int)($_POST['userID']);
  $messageMode = (int)($_POST['messageMode']);
  $superUserID = $_SESSION['ss_id']; 

  $currentDate = date('Y-m-d');
  
  if ( $messageMode == 1 ){ $messageModeStr = "Сведения о регистрации времени удалены. Решение принял: "; }
  if ( $messageMode == 2 ){ $messageModeStr = "Сведения о приходе на рабочее место изменены. Решение принял: "; }

  $messageModeStr = $messageModeStr.get_user_name_by_id( $superUserID );

  $newStartTime = $newInTime; 
  $newEatStartTime = "";
  $newEatStopTime = "";


  $query0 = mysqli_query($link, "SELECT max(ID) FROM ALERTS"); 
 
  $newID = 0;

  $merr=mysqli_error($link);
  if ( !$query0 ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else if ( $row = mysqli_fetch_array($query0) )
  {
    $newID = $row[0] + 1;
  }

  mysqli_set_charset($link, "utf8"); 
  
  $query = mysqli_query($link, "INSERT INTO ALERTS VALUES ( '$newID', '$currentDate', '$userID', '$superUserID', '$messageModeStr', '0')"); 
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    echo "1";
    exit; 
  }  
}

echo "0";

?>
