<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$nextState = (int)($_POST['next']);

if ( isset($_SESSION['ss_id']) )
{
  include_once "/var/www/tori/php_tori/connect.php";
  include_once "/var/www/tori/funcs.php";

  $dtResult = get_current_datetime_in_timezone();

  $dateTimeStr = $dtResult[1];

  $id = $_SESSION['ss_id'];
  $ss_visiting_ID = $_SESSION['ss_visiting_ID'];

  mysqli_set_charset($link, "utf8");

  //go forward
  if ( $nextState == 1 )
  {
    if ( $_SESSION['ss_state'] == 1 )
    { 
      $query=mysqli_query($link, "SELECT a.ID from visiting a where a.ID = (SELECT max(ID) from visiting)");

      $merr=mysqli_error($link);
      if (!$query)
      { 
        echo $merr; 
      }
      else
      {
        $newID = 1;
 
        $vn=mysqli_num_rows($query);
        if ( $vn != 0 )
        {    
          $row = mysqli_fetch_array($query, MYSQLI_ASSOC);                                                                                                                                     
          $newID = $row["ID"] + 1;
        }
        
        $res=mysqli_query($link, "INSERT INTO visiting (ID, user_id, in_dt, eat_start_dt, eat_stop_dt, out_dt, state, remoteWorkState, dayTransitionTime) SELECT distinct '$newID', '$id', '$dateTimeStr', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2', b.RemoteWork, b.dayTransitionTime FROM employees b WHERE b.ID = '$id'");
        $merr=mysqli_error($link);
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
      $res=mysqli_query($link, "UPDATE visiting set eat_start_dt = '$dateTimeStr', state = 3 where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysqli_error($link);
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
      $res=mysqli_query($link, "UPDATE visiting set eat_stop_dt = '$dateTimeStr', state = 4 where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysqli_error($link);
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
      $res=mysqli_query($link, "UPDATE visiting set out_dt = '$dateTimeStr', state = 0 where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysqli_error($link);
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
      $res=mysqli_query($link, "UPDATE visiting set eat_stop_dt = '0000-00-00 00:00:00', out_dt = '0000-00-00 00:00:00', state = 3 where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysqli_error($link);
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
      $res=mysqli_query($link, "UPDATE visiting set eat_start_dt = '0000-00-00 00:00:00', eat_stop_dt = '0000-00-00 00:00:00', state = 2 
                        where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysqli_error($link);
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
      $res=mysqli_query($link, "DELETE FROM visiting where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysqli_error($link);
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
      $res=mysqli_query($link, "DELETE FROM visiting where user_id = '$id' and ID = '$ss_visiting_ID'");
      $merr=mysqli_error($link);
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
      $res=mysqli_query($link, "UPDATE visiting set out_dt = '0000-00-00 00:00:00', state = 4 where ID = '$ss_visiting_ID'");
      $merr=mysqli_error($link);
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
                                                                         