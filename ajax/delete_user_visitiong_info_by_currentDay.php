<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

if ( isset($_POST['userID']) )
{
  $userID = (int)($_POST['userID']);
  $currentDate = date('Y-m-d');
  
  $newStartTime = $newInTime; 
  $newEatStartTime = "";
  $newEatStopTime = "";

  include_once "../php_tori/connect.php";
  include_once "../funcs.php";

  $query = mysql_query("DELETE FROM visiting where date = '$currentDate' and user_id = '$userID'"); 
  $merr=mysql_error();
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
                                                                         