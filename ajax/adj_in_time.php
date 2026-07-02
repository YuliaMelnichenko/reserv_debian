<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( isset($_POST['userID']) AND isset($_POST['inTime']) )
{
  $userID = (int)($_POST['userID']);
  $newInTime = $_POST['inTime'];
  $currentDate = date('Y-m-d');
  
  $newStartTime = $newInTime; 
  $newEatStartTime = "";
  $newEatStopTime = "";

  include_once __DIR__ . "/../funcs.php";
  include_once __DIR__ . "/../php_tori/connect.php";

  $query = mysqli_query($link, "SELECT in_time, out_time, eat_start, eat_stop, state FROM visiting where date = '$currentDate' and user_id = '$userID'"); 
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
          $query = mysqli_query($link, "UPDATE visiting set in_time='$newStartTime', out_time='$newOutTime', eat_start='$newEatStartTime', eat_stop='$newEatStopTime', adj='1' where date = '$currentDate' and user_id = '$userID'");
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
          
          $query = mysqli_query($link, "UPDATE visiting set in_time='$newStartTime', out_time='$newOutTime', eat_start='$newEatStartTime', eat_stop='$newEatStopTime', adj='1' where date = '$currentDate' and user_id = '$userID'");
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
          
          $query = mysqli_query($link, "UPDATE visiting set in_time='$newStartTime', out_time='$newOutTime', eat_start='$newEatStartTime', eat_stop='$newEatStopTime', adj='1' where date = '$currentDate' and user_id = '$userID'");
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
        
        $query = mysqli_query($link, "UPDATE visiting set in_time='$newStartTime', out_time='$newOutTime', eat_start='$newEatStartTime', eat_stop='$newEatStopTime', adj='1' where date = '$currentDate' and user_id = '$userID'");
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