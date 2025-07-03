<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$report_type = $_POST['report_type'];
$start_report_date = $_POST['start_report_date'];
$stop_report_date = $_POST['stop_report_date'];

$currDate = date('Y-m-d');

$_SESSION['rep_stop_date'] = $currDate;
$_SESSION['rep_start_stop_date_set'] = 0; //0 - not set, 1 - one border have been set, 2 - two borders have been set

include_once __DIR__ . "/../funcs.php";

$_SESSION['rep_start_stop_date_mode'] = $report_type;

if ( $report_type == 1 )
{
  $week_day = GetWeekDayD( $currDate );

  $offset = $week_day - 1;

  $_SESSION['rep_start_date'] = DayDecDN( $currDate, $offset );
  $_SESSION['rep_start_stop_date_set'] = 2;
}
else if ( $report_type == 2 )
{
  $month_day = GetMonthDayD( $currDate );
  $offset = $month_day - 1;
  $_SESSION['rep_start_date'] = DayDecDN( $currDate, $offset );
  $_SESSION['rep_start_stop_date_set'] = 2;
}  

else if ( $report_type == 3 )
{
  $month_day = GetMonthDayD( $currDate );
  $offset = $month_day - 1;
  $_SESSION['rep_start_date'] = DayDecDN( $currDate, $offset );
  $_SESSION['rep_start_date'] = MonthDecDN( $_SESSION['rep_start_date'], 1 );

  $_SESSION['rep_start_stop_date_set'] = 2;
}  

else if ( $report_type == 4 )
{
  $month = GetMonthD( $currDate );
  $startMonth = 1;
  $monthOffset = 0;
  if ( $month >= 1 AND $month <= 3 )
  {
    $monthOffset = $month - 1;  
  }
  if ( $month >= 4 AND $month <= 6 )
  {
    $monthOffset = $month - 4;  
  }
  if ( $month >= 7 AND $month <= 9 )
  {
    $monthOffset = $month - 7;  
  }
  if ( $month >= 10 AND $month <= 12 )
  {
    $monthOffset = $month - 10;  
  }

  $newStartDate = MonthDecDN( $currDate, $monthOffset );
  $month_day = GetMonthDayD( $newStartDate );
  $dayOffset = $month_day - 1; 

  $newStartDate = DayDecDN( $newStartDate, $dayOffset );

  $_SESSION['rep_start_date'] = $newStartDate;

  $_SESSION['rep_start_stop_date_set'] = 2;
}  

else if ( $report_type == 5 )
{
  $month = GetMonthD( $currDate );
  $startMonth = 1;
  $monthOffset = 0;
  if ( $month >= 1 AND $month <= 3 )
  {
    $monthOffset = $month - 1;  
  }
  if ( $month >= 4 AND $month <= 6 )
  {
    $monthOffset = $month - 4;  
  }
  if ( $month >= 7 AND $month <= 9 )
  {
    $monthOffset = $month - 7;  
  }
  if ( $month >= 10 AND $month <= 12 )
  {
    $monthOffset = $month - 10;  
  }

  $monthOffset = $monthOffset + 3;
 
  $newStartDate = MonthDecDN( $currDate, $monthOffset );
  $month_day = GetMonthDayD( $newStartDate );
  $dayOffset = $month_day - 1; 
  $newStartDate = DayDecDN( $newStartDate, $dayOffset );
  $_SESSION['rep_start_date'] = $newStartDate;


  $newStopDate = $newStartDate;
  $newStopDate = MonthDecDN( $newStartDate, -3 );
  $newStopDate = DayDecDN( $newStopDate, 1 );

  $_SESSION['rep_stop_date'] = $newStopDate;  

  $_SESSION['rep_start_stop_date_set'] = 2;
}  

else if ( $report_type == 6 )
{ 
  $_SESSION['rep_start_date'] = GetFirstYearDay( GetCurrentYearD( $currDate ) );
  $_SESSION['rep_start_stop_date_set'] = 2;
}

else if ( $report_type == 7 )
{ 
  $_SESSION['rep_start_date'] = $start_report_date;
  $_SESSION['rep_stop_date'] = $stop_report_date;
  $_SESSION['rep_start_stop_date_set'] = 2;
}

echo $report_type."_";

echo "star = ".$_SESSION['rep_start_date']." stop = ".$_SESSION['rep_stop_date'];
?>
