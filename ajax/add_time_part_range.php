<?
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

session_start();

$userID_ = $_SESSION['ss_id']; 

$currentDate = date('Y-m-d');

$add_time_part_start_date = $_POST['add_time_part_start_date'];
$add_time_part_stop_date = $_POST['add_time_part_stop_date'];
$add_time_part_duration = $_POST['add_time_part_duration'];
$add_time_part_base = $_POST['add_time_part_base'];
$add_time_part_desk = $_POST['add_time_part_desk'];
$exclude_weekend_holidays = $_POST['exclude_weekend_holidays'];

$start_dt = 
$stop_dt = 

include_once"../../php_tori/connect.php";

$query0 = mysql_query("SELECT max(ID) FROM ADD_TIME"); 
$newID = 0;
$merr=mysql_error();
if ( !$query0 ) 
{
  echo "<br>mysql_error = $merr<br>";
}
else if ( $row = mysql_fetch_array($query0) )
{
  $newID = $row[0] + 1;
}

if ( strtotime($add_time_part_start_date) > strtotime($add_time_part_stop_date) )
{
  echo "Дата начала диапазона превышает дату окончания";
}
else
{
  include_once  "../funcs.php";

  $daysRange = get_days_range( $add_time_part_start_date, $add_time_part_stop_date );   
  $newDaysRange = array();

  if ( $exclude_weekend_holidays == 1 )
  {
    $weekendsHolidays = get_workdays_holidays_bay_range( $add_time_part_start_date, $add_time_part_stop_date );
    
    foreach ( $daysRange as $rangeDay )
    {
      $found = -1;
      for( $idx = 0; $idx < count( $weekendsHolidays ); $idx ++ )
      {
        if ( $rangeDay == $weekendsHolidays[0][$idx] )
        {
          $found = $weekendsHolidays[1][$idx];
          break;
        }
      }
      if ( $found == -1 )
      {
      if ( isWeekEnd( $rangeDay ) == 0 )
        {
          $newDaysRange[] = $rangeDay;
        }   
      }
      else if ( $found != 0 )
      {
        $newDaysRange[] = $rangeDay;
      }
    }
  }
  else
  {
    $newDaysRange = $daysRange;
  }

  $add_time_part_duration_hour = (int)(substr($add_time_part_duration, 0, 2));
  $add_time_part_duration_min = (int)(substr($add_time_part_duration, 3, 2));

  $daysMinutesCount = 1440;

  $dayDurationMinutes = $add_time_part_duration_hour * 60 + $add_time_part_duration_min;

  if ( $dayDurationMinutes > $daysMinutesCount ){ $dayDurationMinutes = $daysMinutesCount; }

  $startTime = ( $daysMinutesCount - $dayDurationMinutes ) / 2;
  $stopTime = $startTime + $dayDurationMinutes;

  $startTimeStr = format_time_d_hhmmss_pure( $startTime * 60 );
  $stopTimeStr = format_time_d_hhmmss_pure( $stopTime * 60 );

  $err = "";



  foreach( $newDaysRange as $rDay )
  {
    $start = $rDay." ".$startTimeStr;
    $stop = $rDay." ".$stopTimeStr;
  
    mysql_query( 'SET NAMES utf8' ); 
    $query = mysql_query("INSERT INTO ADD_TIME (ID, ADDDATE, USERID, START_DT, STOP_DT, REASON, DESCRIPTION, APPROVED, PAUSE_MODE, BYALERT ) VALUES ('$newID','$currentDate','$userID_','$start','$stop','$add_time_part_base','$add_time_part_desk','0', '0', '$byAlert')");

    $merr=mysql_error();
    if (!$query)
    {
      $err .= "mysql_error = $merr<br>";
      break;
    }
    else
    {
      $newID = $newID + 1;
    }                         
  }
  if ( $err == "" )
  {
    echo "1";       
  } 
  else 
  {
    echo $err;       
  } 
}
?>


                                                                         