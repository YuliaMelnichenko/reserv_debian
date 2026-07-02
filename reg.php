<?php
ob_start();
session_start();

echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/style.css\">";
echo "</head>";
echo "<body bgcolor=\"#ffeeff\">";

if ( isset($_SESSION['ss_id']) )
{
  include_once __DIR__ . "/php_tori/connect.php";

  $time =  date("H:i:s");
  $date_ = date('Y-m-d');
  $id_ = $_SESSION['ss_id'];

  mysqli_set_charset($link, "utf8");
  
  if ( $_SESSION['ss_state'] == 1 )
  {  
    $res=mysqli_query($link, "INSERT INTO visiting (user_id, date, in_time, state) VALUES ('$id_', '$date_', '$time', '2')");
    $merr=mysqli_error($link);
    if (!$res)
    { 
      echo $merr; 
    }
    else
      $_SESSION['ss_state'] = 2;
  }
  else if ( $_SESSION['ss_state'] == 2 )
  {  
    $res=mysqli_query($link, "UPDATE visiting set eat_start = '$time' where user_id = '$id_' and date = '$date_' ");
    $res=mysqli_query($link, "UPDATE visiting set state = 3 where user_id = '$id_' and date = '$date_' ");
    $merr=mysqli_error($lin);
    if (!$res)
    { 
      echo $merr; 
    }
    else
      $_SESSION['ss_state'] = 3;
  }
  else if ( $_SESSION['ss_state'] == 3 )
  { 
    $res=mysqli_query($link, "UPDATE visiting set eat_stop = '$time' where user_id = '$id_' and date = '$date_' ");
    $res=mysqli_query($link, "UPDATE visiting set state = 4 where user_id = '$id_' and date = '$date_' ");
    $merr=mysqli_error($link);
    if (!$res)
    { 
      echo $merr; 
    }
    else
      $_SESSION['ss_state'] = 4;
  }
  else if ( $_SESSION['ss_state'] == 4 )
  {  
    $res=mysqli_query($link, "UPDATE visiting set out_time = '$time' where user_id = '$id_' and date = '$date_' ");
    $res=mysqli_query($link, "UPDATE visiting set state = 0 where user_id = '$id_' and date = '$date_' ");
    $merr=mysqli_error($link);
    if (!$res)
    { 
      echo $merr; 
    }
    else
      $_SESSION['ss_state'] = 0;
  }
  header("Location: index.php");
  exit(); 
}

echo "</body>";
echo "</html>";  
?>