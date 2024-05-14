<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$nextState = (int)($_POST['next']);

if ( isset($_SESSION['ss_id']) )
{
  include_once "../php_tori/connect.php";
  include_once "../funcs.php";

  $dtResult = get_current_datetime_in_timezone();

  $dateTimeStr = $dtResult[1];

  $id = $_SESSION['ss_id'];
  $ss_visiting_ID = $_SESSION['ss_visiting_ID'];

  mysql_query( 'SET NAMES utf8' );

  //go forward
  if ( $nextState == 1 )
  {
    if ( $_SESSION['ss_state'] == 1 )
    { 
      $query=mysql_query("SELECT a.ID from visiting a where a.ID = (SELECT max(ID) from visiting)");

      $merr=mysql_error();
      if (!$query)
      { 
        echo $merr; 
      }
      else
      {
        $newID = 1;
 
        $vn=mysql_num_rows($query);
        if ( $vn != 0 )
        {    
          $row = mysql_fetch_array($query, MYSQL_ASSOC);                                                                                                                                     
          $newID = $row["ID"] + 1;
        }
        
        $res=mysql_query("INSERT INTO visiting (ID, user_id, in_dt, state, remoteWorkState, dayTransitionTime) 
                                SELECT distinct '$newID', '$id', '$dateTimeStr', '2', b.RemoteWork, b.dayTransitionTime FROM employees b WHERE b.ID = '$id'");
        $merr=mysql_error();
        if (!$res)
        { 
          echo $merr; 
        }
        else
        {
          $_SESSION['ss_state'] = 2;
          $_SESSION['ss_visiting_ID'] = $newID;	
          echo "1";
        }
      }
    }
    else if ( $_SESSION['ss_state'] == 2 )
    {  
      $res=mysql_query("UPDATE visiting set eat_start_dt = '$dateTimeStr', state = 3 where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysql_error();
      if (!$res)
      { 
        echo $merr; 
      }
      else
      {
        $_SESSION['ss_state'] = 3;
        echo "1";
      }
    }
    else if ( $_SESSION['ss_state'] == 3 )
    { 
      $res=mysql_query("UPDATE visiting set eat_stop_dt = '$dateTimeStr', state = 4 where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysql_error();
      if (!$res)
      { 
        echo $merr; 
      }
      else
      {
        $_SESSION['ss_state'] = 4;
        echo "1";
      }
    }
    else if ( $_SESSION['ss_state'] == 4 )
    {  
      $res=mysql_query("UPDATE visiting set out_dt = '$dateTimeStr', state = 0 where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysql_error();
      if (!$res)
      { 
        echo $merr; 
      }
      else
      {
        $_SESSION['ss_state'] = 0;
        echo "1";
      }
    }
  }
  //go back
  else
  {
    if ( $_SESSION['ss_state'] == 4 )
    {  
      $res=mysql_query("UPDATE visiting set eat_stop_dt = '0000-00-00 00:00:00', out_dt = '0000-00-00 00:00:00', state = 3 where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysql_error();
      if (!$res)
      { 
        echo $merr; 
      }
      else
      {
        $_SESSION['ss_state'] = 3;
        echo "1";
      }
    }
    else if ( $_SESSION['ss_state'] == 3 )
    { 
      $res=mysql_query("UPDATE visiting set eat_start_dt = '0000-00-00 00:00:00', eat_stop_dt = '0000-00-00 00:00:00', state = 2 
                        where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysql_error();
      if (!$res)
      { 
        echo $merr; 
      }
      else
      {
        $_SESSION['ss_state'] = 2;
        echo "1";
      }
    }
    else if ( $_SESSION['ss_state'] == 2 )
    {  
      $res=mysql_query("DELETE FROM visiting where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysql_error();
      if (!$res)
      { 
        echo $merr; 
      }
      else
      {
        $_SESSION['ss_state'] = 1;
        echo "1";
      }
    }
    else if ( $_SESSION['ss_state'] == 1 )
    {  
      $res=mysql_query("DELETE FROM visiting where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysql_error();
      if (!$res)
      { 
        echo $merr; 
      }
      else
      {
        $_SESSION['ss_state'] = 0;
        echo "1";
      }
    }
    else if ( $_SESSION['ss_state'] == 0 )
    {  
      $res=mysql_query("UPDATE visiting set out_dt = '0000-00-00 00:00:00', state = 4 where ID = '$ss_visiting_ID'");
      $merr=mysql_error();
      if (!$res)
      { 
        echo $merr; 
      }
      else
      {
        $_SESSION['ss_state'] = 4;
        echo "1";
      }
    }
  }
}
else
{
  echo "Ошибка 485";
}

?>    
                                                                         