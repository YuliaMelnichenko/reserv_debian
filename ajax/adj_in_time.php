<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_superuser();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( isset($_POST['userID']) AND isset($_POST['inTime']) )
{
  $userID = (int)($_POST['userID']);
  $newInTime = trim($_POST['inTime']);

  if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $newInTime)) {
    echo "-13";
    exit;
  }

  if (strlen($newInTime) == 5) {
    $newInTime .= ':00';
  }
  $currentDate = date('Y-m-d');
  
  $newStartTime = $newInTime; 
  $newEatStartTime = "";
  $newEatStopTime = "";

  include_once __DIR__ . "/../funcs.php";
  include_once __DIR__ . "/../php_tori/connect.php";

  $query = db_query($link, "SELECT in_time, out_time, eat_start, eat_stop, state FROM visiting WHERE date = ? AND user_id = ?", 'si', array($currentDate, $userID));
  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    $days_errors[] = "MYSQL : $merr";
  }
  else
  {
    if ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {
      $inTime = $row["in_time"];
      $outTime = $row["out_time"];
      $eatStart = $row["eat_start"];
      $eatStop = $row["eat_stop"];
      $state = $row["state"];

      if ( $state == 0 )
      {
        $dayDuration = strtotime( $outTime ) - strtotime( $inTime ) - ( strtotime( $eatStop ) - strtotime( $eatStart ) );
        $offsetToDelete = strtotime( $newInTime ) - strtotime( "10:00:00" );

        if ( strtotime( $newInTime ) > strtotime( $outTime ) )
        {
          echo "-10";
          exit;
        }
        else
        {
          if ( strtotime( $newInTime ) > strtotime( $eatStart ) )
          {
            $offset = strtotime( $newInTime ) - strtotime( $eatStart ) + 1;
            
            $offset = format_time_d_hhmmss_pure( $offset );   
             
            $newEatStartTime = inc_time_by_time( $eatStart, $offset );
            $newEatStopTime = inc_time_by_time( $eatStop, $offset );
            
            if ( strtotime( $newEatStopTime ) > strtotime( $outTime ) )
            { 
              $newOutTime = inc_time_by_time( $newEatStopTime, "00:00:01" );
            }
          }
          else
          {
            $newEatStartTime = $eatStart;
            $newEatStopTime = $eatStop;
            $newOutTime = $outTime;             
          }
          $query = db_execute($link, "UPDATE visiting SET in_time = ?, out_time = ?, eat_start = ?, eat_stop = ?, adj = 1 WHERE date = ? AND user_id = ?", 'sssssi', array($newStartTime, $newOutTime, $newEatStartTime, $newEatStopTime, $currentDate, $userID));
          $merr=mysqli_error($link);
          if ( !$query ) 
          {
            $days_errors[] = "MYSQL : $merr";
          }
          echo "1";
          exit;
        }
      }
      else if ( $state == 4 )
      {
        if ( strtotime( $newInTime ) > strtotime( $eatStart ) )
        {
          echo "-11";
          exit;
        }
        else
        {
          if ( strtotime( $newInTime ) > strtotime( $eatStart ) )
          {
            $offset = strtotime( $newInTime ) - strtotime( $eatStart ) + 1;
            
            format_time_d_hhmmss_pure( $offset );   
             
            $newEatStartTime = inc_time_by_time( $eatStart, $offset );
            $newEatStopTime = inc_time_by_time( $eatStop, $offset );
            $newOutTime = $outTime;
          }
          else
          {
            $newEatStartTime = $eatStart;
            $newEatStopTime = $eatStop;
            $newOutTime = $outTime;             
          }
          
          $query = db_execute($link, "UPDATE visiting SET in_time = ?, out_time = ?, eat_start = ?, eat_stop = ?, adj = 1 WHERE date = ? AND user_id = ?", 'sssssi', array($newStartTime, $newOutTime, $newEatStartTime, $newEatStopTime, $currentDate, $userID));
          $merr=mysqli_error($link);
          if ( !$query ) 
          {
            $days_errors[] = "MYSQL : $merr";
          }
          echo "2";
          exit;
        }
      }
      else if ( $state == 3 )
      {
        $dayDuration = strtotime( $inTime ) - strtotime( $eatStart );
        $offsetToDelete = strtotime( $newInTime ) - strtotime( "10:00:00" );
        if ( $dayDuration < $offsetToDelete )
        {
          echo "-12";
          exit;
        }
        else
        {
          if ( strtotime( $newInTime ) > strtotime( $eatStart ) )
          {
            $offset = strtotime( $newInTime ) - strtotime( $eatStart ) + 1;
            
            format_time_d_hhmmss_pure( $offset );   
             
            $newEatStartTime = inc_time_by_time( $eatStart, $offset );
            $newEatStopTime = $eatStop;
            $newOutTime = $outTime;
          }
          else
          {
            $newEatStartTime = $eatStart;
            $newEatStopTime = $eatStop;
            $newOutTime = $outTime;             
          }
          
          $query = db_execute($link, "UPDATE visiting SET in_time = ?, out_time = ?, eat_start = ?, eat_stop = ?, adj = 1 WHERE date = ? AND user_id = ?", 'sssssi', array($newStartTime, $newOutTime, $newEatStartTime, $newEatStopTime, $currentDate, $userID));
          $merr=mysqli_error($link);
          if ( !$query ) 
          {
            $days_errors[] = "MYSQL : $merr";
          }
          echo "2";
          exit;
        }
      }
      else if ( $state == 2 )
      {
        $newEatStartTime = $eatStart;
        $newEatStopTime = $eatStop;
        $newOutTime = $outTime;             
        
        $query = db_execute($link, "UPDATE visiting SET in_time = ?, out_time = ?, eat_start = ?, eat_stop = ?, adj = 1 WHERE date = ? AND user_id = ?", 'sssssi', array($newStartTime, $newOutTime, $newEatStartTime, $newEatStopTime, $currentDate, $userID));
        $merr=mysqli_error($link);
        if ( !$query ) 
        {
          $days_errors[] = "MYSQL : $merr";
        }
        echo "3";
        exit;
      }
    } 
  }  
}
echo "0";
?>
