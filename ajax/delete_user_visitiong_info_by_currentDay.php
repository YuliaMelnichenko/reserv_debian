<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if ( isset($_POST['userID']) )
{
  $userID = (int)($_POST['userID']);
  $currentDate = date('Y-m-d');

  if ($userID <= 0) {
    deny_ajax_access(400, 'INVALID_USER');
  }

  require_ajax_supervisor_for_user($userID, 3);

  include_once __DIR__ . "/../funcs.php";
  include_once __DIR__ . "/../php_tori/connect.php";

  $query = db_execute(
    $link,
    'DELETE FROM visiting WHERE date = ? AND user_id = ?',
    'si',
    array($currentDate, $userID)
  );
  $merr = mysqli_error($link);
  if ( !$query ) 
  {
    $days_errors[] = "MYSQL : $merr";
  }
  else
  {
    echo "1";
    exit; 
  }  
}
echo "0";
?>
