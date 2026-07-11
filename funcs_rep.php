<?php

if (empty($_SESSION['rep_start_date']) || empty($_SESSION['rep_stop_date'])) {
    die('Ошибка: не задан период отчета');
}

if (strtotime($_SESSION['rep_start_date']) > strtotime($_SESSION['rep_stop_date'])) {
    die('Ошибка: некорректный диапазон дат');
}

function get_stat_set_by_range_full_ex( $startDate, $stopDate, $userID, $userRate ){
  $userDayNorm = ( $userRate / 5 ) * 60 * 60;

  include __DIR__ . "/php_tori/connect.php";
  include_once __DIR__ . "/funcs.php";

  $days_dates_set = array();
  $days_dates_start_set = array();
  $days_dates_stop_set = array();

  $days_work_start = array();
  $days_work_stop = array();
  $days_eat_start = array();
  $days_eat_stop = array();

  $days_add_infos = array();

  $days_is_there_work_time = array();

  $days_day_type = array();  // 0 - work day
                             // 1 - weekend
                             // 2 - holiday
                             // 3 - current day

  $days_day_state = array();

  $days_day_currday = array();


  $days_errors = array();

  $days_penalties = array();
  $days_penalty_reasons = array();
  $days_penalty_supervisor = array();
  $days_penalty_durationVal = array();

  $days_penalty_remoteWorkState = array();
  $days_penalty_timeZoneSec = array();
  $days_penalty_dayTransitionTime = array();


  $days_norm = array();

  $temp_days_dayTransitionTime_temp = array();
  $temp_days_work_start = array();
  $temp_days_work_stop = array();
  $temp_days_add_infos = array();
  $temp_days_eat_start = array();
  $temp_days_eat_stop = array();
  $temp_days_is_there_work_time = array();
  $temp_days_day_type = array(); 
  $temp_days_day_state = array(); 
  $temp_days_day_currday = array(); 
  $temp_days_penalties = array();
  $temp_days_penalty_reasons = array();
  $temp_days_penalty_supervisor = array();
  $temp_days_penalty_durationVal = array();

  $temp_days_penalty_remoteWorkState = array();
  $temp_days_penalty_timeZoneSec = array();
  $temp_days_penalty_dayTransitionTime = array();


  $tempDates = array();

  for ( $day = $startDate;; $day = DayIncDN( $day, 1 ) )  
  {
    $dayVal = strtotime( $day );  
    $start_dt = date("Y-m-d H:i:s", $dayVal);
    $stop_dt = date("Y-m-d H:i:s", $dayVal + 60*60*24 - 1);

    $days_dates_set[] = $day;
    $days_dates_start_set[] = $start_dt;
    $days_dates_stop_set[] = $stop_dt;

    if ( $day == $stopDate ){ break; }
  }

  for ( $dayNum = 0; $dayNum < count($days_dates_set); $dayNum ++ )
  {

    if ( $dayNum < count($days_dates_set) )
    {
      $start_dt = $days_dates_start_set[$dayNum];
      $stop_dt = $days_dates_stop_set[$dayNum];
    }

    if ( $dayNum == count($days_dates_set) )
    {
      $start_dt = date("Y-m-d H:i:s", strtotime($start_dt."+ 1 day"));
      $stop_dt = date("Y-m-d H:i:s", strtotime($stop_dt."+ 1 day"));
    } 

    $addTimePauseTempArray = get_add_work_info_by_user_and_day_ex( $userID, $start_dt, $stop_dt, 1 );

    if ( count( $addTimePauseTempArray ) != 0 )
    {
      $days_add_infos[] = $addTimePauseTempArray;
    }
    else
    {
      $days_add_infos[] = "NDF"; 
    }   
  }  

  $dayTypesByDate = get_work_dayoff_types_by_range($link, $startDate, $stopDate);
  $currentDateArr = get_current_datetime_in_timezone();
  $currentDate = $currentDateArr[2];


  unset($tempDates);
  $tempDates = array();   

  $query = mysqli_query($link, "SELECT DISTINCT dayTransitionTime, user_id, state, in_dt, eat_start_dt, eat_stop_dt, out_dt, remoteWorkState, timeZoneSec, dayTransitionTime, 
                        TIMESTAMP('$startDate', dayTransitionTime) as dt1, TIMESTAMP('$stopDate', dayTransitionTime) as dt2
                        FROM visiting 
                        where 
                        in_dt >= TIMESTAMP('$startDate', dayTransitionTime) 
                          and 
                        user_id = '$userID'"); 

  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    $days_errors[] = "MYSQL : $merr";
  }
  else
  {
    while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {
      $in_dt_temp = $row["in_dt"];
      $dayTransitionTime_temp = $row["dayTransitionTime"];

      $dt1 = $row["dt1"];
      $dt2 = $row["dt2"];

      $in_dt_temp_val = strtotime($in_dt_temp);
      $dayTransitionTime_temp_val = strtotime($dayTransitionTime_temp);    

      $in_dt_temp_shift = shift_dt_by_transition_time( $in_dt_temp, $dayTransitionTime_temp, -1 ) ;

      $in_dt_temp_shift = date("Y-m-d", strtotime($in_dt_temp_shift));

      $tempDates[] = $in_dt_temp_shift;
      $currentDay = 0;
      if ( $currentDate == $in_dt_temp_shift )
        $currentDay = 1;

      $temp_days_work_start[] = $in_dt_temp;
      $temp_days_work_stop[] = $row["out_dt"];
      $temp_days_eat_start[] = $row["eat_start_dt"];
      $temp_days_eat_stop[] = $row["eat_stop_dt"];
      $temp_state = $row["state"];
      $temp_days_day_type[] = 10 + $temp_state;
      $temp_days_day_state[] = $temp_state;
      $temp_days_day_currday[] = $currentDay;

      $temp_days_dayTransitionTime_temp[] = $dayTransitionTime_temp;

      $temp_days_remoteWorkState[] = $row["remoteWorkState"];
      $temp_days_timeZoneSec[] = $row["timeZoneSec"];
      $temp_days_dayTransitionTime[] = $row["dayTransitionTime"];

    }
  }

  foreach ( $days_dates_set as $day )
  {
    $found = 0;
    for ( $idx = 0; $idx < count( $tempDates ); $idx ++ )
    {
      if ( $day == $tempDates[$idx] )
      {
        $days_work_start[] = $temp_days_work_start[$idx];
        $days_work_stop[] = $temp_days_work_stop[$idx];
        $days_eat_start[] = $temp_days_eat_start[$idx];
        $days_eat_stop[] = $temp_days_eat_stop[$idx];
	      $days_is_there_work_time[] = 1;
        $days_day_type[] = $temp_days_day_type[$idx]; 
        $day_st = $temp_days_day_state[$idx];
        $days_day_state[] = $day_st;
        $days_day_currday[] = $temp_days_day_currday[$idx];

        $days_remoteWorkState[] = $temp_days_remoteWorkState[$idx];
        $days_timeZoneSec[] = $temp_days_timeZoneSec[$idx];
        $days_dayTransitionTime[] = $temp_days_dayTransitionTime[$idx];

        if ( isWeekEnd( $day ) == 1 )
        {
          $days_norm[] = 0;
        }
        else
        {
          $days_norm[] = $userDayNorm;
        }
        $found = 1;
        break;
      }
    }
    if ( $found == 0 )
    {
      $days_work_start[] = "0000-00-00 00:00:00";
      $days_work_stop[] = "0000-00-00 00:00:00";
      $days_eat_start[] = "0000-00-00 00:00:00";
      $days_eat_stop[] = "0000-00-00 00:00:00";
      $days_is_there_work_time[] = 0;
      $days_day_type[] = "NDF";
      $days_day_state[] = "NDF";
      $days_day_currday[] = "NDF";

      $days_remoteWorkState[] = "NDF";
      $days_timeZoneSec[] = "NDF";
      $days_dayTransitionTime[] = "NDF";

      if ( isWeekEnd( $day ) == 1 )
      {
        $days_norm[] = 0;
      }
      else
      {
        $days_norm[] = $userDayNorm;
      }
    }
  }

  for ( $idxd = 0; $idxd < count( $days_dates_set ); $idxd ++ ){
    $day = $days_dates_set[$idxd];
    $add = 0;

    if (isset($dayTypesByDate[$day])) {
      if ($dayTypesByDate[$day] == 0) {
        $add = 100;
        $days_norm[$idxd] = 0;
      }
      else if ($dayTypesByDate[$day] == 1) {
        $add = 200;
        $days_norm[$idxd] = $userDayNorm;
      }
      else if ($dayTypesByDate[$day] == 2) {
        $add = 300;

        if ($days_norm[$idxd] > 0) {
          $days_norm[$idxd] = max(0, $days_norm[$idxd] - 3600);
        }
      }
    }

    if ( $add != 0 )
    {
      if ( $days_day_type[$idxd] != "NDF" )
      {
        $days_day_type[$idxd] = $days_day_type[$idxd] + $add;
      }
      else
      {
        $days_day_type[$idxd] = $add;
      }
    }
  }

  $days_norm_before_leaves = $days_norm;

  $days_norm = apply_staff_leaves_to_days_norm($link, $userID, $startDate, $stopDate, $days_dates_set, $days_norm);

  $days_leave_events = get_staff_leave_events_by_days($link, $userID, $startDate, $stopDate, $days_dates_set);

  unset($tempDates);
  $tempDates = array();   
 
  $query = mysqli_query($link, "SELECT distinct a.date, a.supervisorID, a.reason, b.duration 
                        FROM Penalty a join Delays b on a.id = b.penaltyID 
                        WHERE a.date >= '$startDate' AND a.date <= '$stopDate' AND a.userID = '$userID'"); 

  $merr=mysqli_error($link);
  if ( !$query ) 
  {
    $days_errors[] = "MYSQL : $merr";
  }
  else
  {
    while ( $row = mysqli_fetch_array($query, MYSQLI_ASSOC) )
    {
      $date = $row["date"];
      $tempDates[] = $date;
      $currentDay = 0;
      if ( $currentDate == $date )
        $currentDay = 1;

      $temp_days_penalties[] = 1;
      $temp_days_penalty_reasons[] = $row["reason"];
      $temp_days_penalty_supervisor[] = $row["supervisorID"];
      
      $temp_days_penalty_durationStr = $row["duration"];
      $temp_days_penalty_durationValue = time_to_second( $temp_days_penalty_durationStr );
      $temp_days_penalty_durationVal[] = $temp_days_penalty_durationValue;

    }
  }

  foreach ( $days_dates_set as $day )
  {
    $found = 0;
    for ( $idx = 0; $idx < count( $tempDates ); $idx ++ )
    {
      if ( $day == $tempDates[$idx] )
      {
        $days_penalties[] = $temp_days_penalties[$idx];
        $days_penalty_reasons[] = $temp_days_penalty_reasons[$idx];
        $days_penalty_supervisor[] = $temp_days_penalty_supervisor[$idx];
        $days_penalty_durationVal[] = $temp_days_penalty_durationVal[$idx];
        $found = 1;

        break;
      }
    }
    if ( $found == 0 )
    {
      $days_penalties[] = "NDF";
      $days_penalty_reasons[] = "NDF";
      $days_penalty_supervisor[] = "NDF";
      $days_penalty_durationVal[] = 0;
    }
  }
  
  $stat_results = array();
  $stat_result_value = array();  // [0] - start date
                                 // [1] - stop date
                                 // [2] - pure duration
                                 // [3] - add infos
                                 //    [0] - time start
                                 //    [1] - time stop
                                 //    [2] - reason
                                 //    [3] - description
                                 //    [4] - approved; 
                                 //    [5] - pause mode; 
                                 // [4] - eat duration  
                                 // [5] - type // 1 - for first perion
                                               // 2 - from start to stop date
                                               // 3 - week
                                               // 4 - month
                                               // 5 - quarter
                                               // 6 - year
                                 // [6] - day norm
                                 // [7] - penalties duration
                                 // [8] - penalties count

  $firstPeriodDate = "";
  $skipDaysCount = 0;

  $periodOpened = 0;
  $weekOpened = 0;
  $monthOpened = 0;
  $quarterOpened = 0;
  $yearOpened = 0;

  $resultPureDuration = 0;
  $addTimeDuration = 0;
  $lunchDuration = 0;
  $dayNorm = 0;
  $PenaltiesDuration = 0;
  $PenaltiesCount = 0;

  $resultPureDurationWholePeriod = 0;
  $addTimeDurationWholePeriod = 0;
  $pauseTimeDurationWholePeriod = 0;
  $lunchDurationWholePeriod = 0;
  $dayNormWholePeriod = 0;
  $PenaltiesDurationWholePeriod = 0;
  $PenaltiesCountWholePeriod = 0;
  $dayNormBeforeLeavesWholePeriod = 0;
  $leaveDurationWholePeriod = 0;

  $resultPureDurationPeriod = 0;
  $addTimeDurationPeriod = 0;
  $pauseTimeDurationPeriod = 0;
  $lunchDurationPeriod = 0;
  $dayNormPeriod = 0;
  $PenaltiesDurationPeriod = 0;
  $PenaltiesCountPeriod = 0;
  $dayNormBeforeLeavesPeriod = 0;
  $leaveDurationPeriod = 0;

  $resultPureDurationWeek = 0;
  $addTimeDurationWeek = 0;
  $pauseTimeDurationWeek = 0;
  $lunchDurationWeek = 0;
  $dayNormWeek = 0;
  $PenaltiesDurationWeek = 0;
  $PenaltiesCountWeek = 0;
  $dayNormBeforeLeavesWeek = 0;
  $leaveDurationWeek = 0;

  $resultPureDurationMonth = 0;
  $addTimeDurationMonth = 0;
  $pauseTimeDurationMonth = 0;
  $lunchDurationMonth = 0;
  $dayNormMonth = 0;
  $PenaltiesDurationMonth = 0;
  $PenaltiesCountMonth = 0;
  $dayNormBeforeLeavesMonth = 0;
  $leaveDurationMonth = 0;

  $resultPureDurationQuarter = 0;
  $addTimeDurationQuarter = 0;
  $pauseTimeDurationQuarter = 0;
  $lunchDurationQuarter = 0;
  $dayNormQuarter = 0;
  $PenaltiesDurationQuarter = 0;
  $PenaltiesCountQuarter = 0;
  $dayNormBeforeLeavesQuarter = 0;
  $leaveDurationQuarter = 0;

  $resultPureDurationYear = 0;
  $addTimeDurationYear = 0;
  $pauseTimeDurationYear = 0;
  $lunchDurationYear = 0;
  $dayNormYear = 0;
  $PenaltiesDurationYear = 0;
  $PenaltiesCountYear = 0;
  $dayNormBeforeLeavesYear = 0;
  $leaveDurationYear = 0;


  if ( count( $days_dates_set ) > 0 )
  {
    $firstPeriodDate = $days_dates_set[0];   
  }

  $day = "";

  $dayNormBeforeLeaves = 0;
  $leaveDuration = 0;

  for ( $idx = 0; $idx <= count( $days_dates_set ); $idx ++ )
  {
    if ( $idx < count( $days_dates_set ) )
    {
      $day = $days_dates_set[$idx];  
    }
    else
    {
      $day = DayIncDN( $day, 1 );
    } 
         
   if ( $idx < count( $days_dates_set ) ){
      $day_day[] = $days_dates_set[$idx];
      $day_work_start = $days_work_start[$idx];
      $day_work_stop = $days_work_stop[$idx];
      $days_add_info = $days_add_infos[$idx];
      $day_eat_start = $days_eat_start[$idx];
      $day_eat_stop = $days_eat_stop[$idx];
      $day_day_state = $days_day_state[$idx];
      $day_penalties = $days_penalties[$idx];
      $day_penalty_duration = $days_penalty_durationVal[$idx];

      $day_norm = $days_norm[$idx];
      $day_day_currday = $days_day_currday[$idx];

      $durations = get_durations( $day_work_start, $day_work_stop, $day_eat_start, $day_eat_stop, $days_add_info, $day_day_state, $day_day_currday );

      $resultPureDuration = $durations[3];
      $addTimeDuration = $durations[2];
      $pauseTimeDuration = $durations[5];
      $lunchDuration = $durations[1];  
      $dayNorm = $day_norm;

      $dayNormBeforeLeaves = $days_norm_before_leaves[$idx];
      $leaveDuration = $dayNormBeforeLeaves - $dayNorm;
  
      $PenaltiesDuration = 0;
      $PenaltiesCount = 0; 
      if ( $day_penalties == 1 ){
        $PenaltiesCount = 1; 
        $PenaltiesDuration = $day_penalty_duration;  
      }
    }
    else{
      $resultPureDuration = 0;
      $addTimeDuration = 0;
      $pauseTimeDuration = 0;
      $lunchDuration = 0;
      $dayNorm = 0;
      $PenaltiesDuration = 0;
      $PenaltiesCount = 0;
      $dayNormBeforeLeaves = 0;
      $leaveDuration = 0;
    }
    
    if ( $weekOpened == 0 && is_first_week_day( $day ) ){
      $weekOpened = 1;
      $periodOpened = -1;

      if ( $day != $firstPeriodDate ){
        $stat_result_value[0] = $firstPeriodDate;
        $stat_result_value[1] = $day;
        $stat_result_value[2] = $resultPureDurationPeriod; 
        $stat_result_value[3] = $addTimeDurationPeriod; 
        $stat_result_value[4] = $lunchDurationPeriod; 
        $stat_result_value[5] = 1;
        $stat_result_value[6] = $dayNormPeriod;
        $stat_result_value[7] = $PenaltiesDurationPeriod;
        $stat_result_value[8] = $PenaltiesCountPeriod;
        $stat_result_value[9] = $pauseTimeDurationPeriod;
        $stat_result_value[10] = $dayNormBeforeLeavesPeriod;
        $stat_result_value[11] = $leaveDurationPeriod;
        $stat_results[] = $stat_result_value;

      }
    } 
    else if ( $weekOpened == 1 && is_first_week_day( $day ) )
    {
      $startDate22 = DayIncDN( $day, -7 );
      $stopDate22 = DayIncDN( $day, 0 );

      $stat_result_value[0] = $startDate22;
      $stat_result_value[1] = $stopDate22;
      $stat_result_value[2] = $resultPureDurationWeek; 
      $stat_result_value[3] = $addTimeDurationWeek; 
      $stat_result_value[4] = $lunchDurationWeek; 
      $stat_result_value[5] = 3;
      $stat_result_value[6] = $dayNormWeek;
      $stat_result_value[7] = $PenaltiesDurationWeek;
      $stat_result_value[8] = $PenaltiesCountWeek;
      $stat_result_value[9] = $pauseTimeDurationWeek;
      $stat_result_value[10] = $dayNormBeforeLeavesWeek;
      $stat_result_value[11] = $leaveDurationWeek;
      $stat_results[] = $stat_result_value;

      $resultPureDurationWeek = 0;
      $addTimeDurationWeek = 0;
      $pauseTimeDurationWeek = 0;
      $lunchDurationWeek = 0;
      $dayNormWeek = 0;
      $PenaltiesDurationWeek = 0;
      $PenaltiesCountWeek = 0;
      $dayNormBeforeLeavesWeek = 0;
      $leaveDurationWeek = 0;
    }
              
    if ( $monthOpened == 0 && is_first_month_day( $day ) )
    {
      $monthOpened = 1;  
    }                   
    else if ( $monthOpened == 1 AND is_first_month_day( $day ) )
    {
      $stopDate22 = DayIncDN( $day, 0 );               
      $startDate22 = GetFirstMonthDayEx( $stopDate22 );

      $stat_result_value[0] = $startDate22;
      $stat_result_value[1] = $stopDate22;
      $stat_result_value[2] = $resultPureDurationMonth; 
      $stat_result_value[3] = $addTimeDurationMonth; 
      $stat_result_value[4] = $lunchDurationMonth; 
      $stat_result_value[5] = 4;
      $stat_result_value[6] = $dayNormMonth;
      $stat_result_value[7] = $PenaltiesDurationMonth;
      $stat_result_value[8] = $PenaltiesCountMonth;
      $stat_result_value[9] = $pauseTimeDurationMonth;
      $stat_result_value[10] = $dayNormBeforeLeavesMonth;
      $stat_result_value[11] = $leaveDurationMonth;

      $stat_results[] = $stat_result_value;

      $resultPureDurationMonth = 0;
      $addTimeDurationMonth = 0;
      $pauseTimeDurationMonth = 0;
      $lunchDurationMonth = 0;
      $dayNormMonth = 0;
      $PenaltiesDurationMonth = 0;
      $PenaltiesCountMonth = 0;
      $dayNormBeforeLeavesMonth = 0;
      $leaveDurationMonth = 0;
    }                 
    if ( $quarterOpened == 0 AND is_first_quarter_day( $day ) )
    {
      $quarterOpened = 1;
    }
    else if ( $quarterOpened == 1 AND is_first_quarter_day( $day ) )
    {
      $stopDate22 = DayIncDN( $day, 0 );               
      $startDate22 = GetFirstQuarterDayEx( $stopDate22 );

      $stat_result_value[0] = $startDate22;
      $stat_result_value[1] = $stopDate22;
      $stat_result_value[2] = $resultPureDurationQuarter; 
      $stat_result_value[3] = $addTimeDurationQuarter; 
      $stat_result_value[4] = $lunchDurationQuarter; 
      $stat_result_value[5] = 5;
      $stat_result_value[6] = $dayNormQuarter;
      $stat_result_value[7] = $PenaltiesDurationQuarter;
      $stat_result_value[8] = $PenaltiesCountQuarter;
      $stat_result_value[9] = $pauseTimeDurationQuarter;
      $stat_result_value[10] = $dayNormBeforeLeavesQuarter;
      $stat_result_value[11] = $leaveDurationQuarter;

      $stat_results[] = $stat_result_value;

      $resultPureDurationQuarter = 0;
      $addTimeDurationQuarter = 0;
      $pauseTimeDurationQuarter = 0;
      $lunchDurationQuarter = 0;
      $dayNormQuarter = 0;
      $PenaltiesDurationQuarter = 0;
      $PenaltiesCountQuarter = 0;
      $dayNormBeforeLeavesQuarter = 0;
      $leaveDurationQuarter = 0;
    }                 

    if ( $yearOpened == 0 && is_first_year_day( $day ) )
    {
      $yearOpened = 1;
    }
    else if ( $yearOpened == 1 AND is_first_year_day( $day ) )
    {
      $stopDate22 = DayIncDN( $day, -1 );               
      $startDate22 = GetFirstYearDayEx( $stopDate22 );

      $stat_result_value[0] = $startDate22;
      $stat_result_value[1] = $stopDate22;
      $stat_result_value[2] = $resultPureDurationYear; 
      $stat_result_value[3] = $addTimeDurationYear; 
      $stat_result_value[4] = $lunchDurationYear; 
      $stat_result_value[5] = 6;
      $stat_result_value[6] = $dayNormYear;
      $stat_result_value[7] = $PenaltiesDurationYear;
      $stat_result_value[8] = $PenaltiesCountYear;
      $stat_result_value[9] = $pauseTimeDurationYear;
      $stat_result_value[10] = $dayNormBeforeLeavesYear;
      $stat_result_value[11] = $leaveDurationYear;

      $stat_results[] = $stat_result_value;

      $resultPureDurationYear = 0;
      $addTimeDurationYear = 0;
      $pauseTimeDurationYear = 0;
      $lunchDurationYear = 0;
      $dayNormYear = 0;
      $PenaltiesDurationYear = 0;
      $PenaltiesCountYear = 0;
      $dayNormBeforeLeavesYear = 0;
      $leaveDurationYear = 0;
    }                   

    if ( $weekOpened == 0 )
    {
      $periodOpened = 1;
    }
        
    if ( $periodOpened == 1 )
    {
      $resultPureDurationPeriod += $resultPureDuration; 
      $addTimeDurationPeriod += $addTimeDuration;
      $pauseTimeDurationPeriod += $pauseTimeDuration;
      $lunchDurationPeriod += $lunchDuration;  
      $dayNormPeriod += $dayNorm;
      $PenaltiesDurationPeriod += $PenaltiesDuration;
      $PenaltiesCountPeriod += $PenaltiesCount;
      $dayNormBeforeLeavesPeriod += $dayNormBeforeLeaves;
      $leaveDurationPeriod += $leaveDuration;

    }

    if ( $weekOpened == 1 )
    {
      $resultPureDurationWeek += $resultPureDuration; 
      $addTimeDurationWeek += $addTimeDuration;  
      $pauseTimeDurationWeek += $pauseTimeDuration;
      $lunchDurationWeek += $lunchDuration;  
      $dayNormWeek += $dayNorm;
      $PenaltiesDurationWeek += $PenaltiesDuration;
      $PenaltiesCountWeek += $PenaltiesCount;
      $dayNormBeforeLeavesWeek += $dayNormBeforeLeaves;
      $leaveDurationWeek += $leaveDuration;
    }

    if ( $monthOpened == 1 )
    {
      $resultPureDurationMonth += $resultPureDuration; 
      $addTimeDurationMonth += $addTimeDuration;
      $pauseTimeDurationMonth += $pauseTimeDuration;
      $lunchDurationMonth += $lunchDuration;  
      $dayNormMonth += $dayNorm;
      $PenaltiesDurationMonth += $PenaltiesDuration;
      $PenaltiesCountMonth += $PenaltiesCount;
      $dayNormBeforeLeavesMonth += $dayNormBeforeLeaves;
      $leaveDurationMonth += $leaveDuration;
    }

    if ( $quarterOpened == 1 )
    {
      $resultPureDurationQuarter += $resultPureDuration; 
      $addTimeDurationQuarter += $addTimeDuration;
      $pauseTimeDurationQuarter += $pauseTimeDuration;
      $lunchDurationQuarter += $lunchDuration;  
      $dayNormQuarter += $dayNorm;
      $PenaltiesDurationQuarter += $PenaltiesDuration;
      $PenaltiesCountQuarter += $PenaltiesCount;
      $dayNormBeforeLeavesQuarter += $dayNormBeforeLeaves;
      $leaveDurationQuarter += $leaveDuration;
    }

    if ( $yearOpened == 1 )
    {
      $resultPureDurationYear += $resultPureDuration; 
      $addTimeDurationYear += $addTimeDuration;
      $pauseTimeDurationYear += $pauseTimeDuration;
      $lunchDurationYear += $lunchDuration;  
      $dayNormYear += $dayNorm;
      $PenaltiesDurationYear += $PenaltiesDuration;
      $PenaltiesCountYear += $PenaltiesCount;
      $dayNormBeforeLeavesYear += $dayNormBeforeLeaves;
      $leaveDurationYear += $leaveDuration;
    }                   

    $resultPureDurationWholePeriod += $resultPureDuration;
    $addTimeDurationWholePeriod += $addTimeDuration;
    $pauseTimeDurationWholePeriod += $pauseTimeDuration;
    $lunchDurationWholePeriod += $lunchDuration;
    $dayNormWholePeriod += $dayNorm;
    $PenaltiesDurationWholePeriod += $PenaltiesDuration;
    $PenaltiesCountWholePeriod += $PenaltiesCount;
    $dayNormBeforeLeavesWholePeriod += $dayNormBeforeLeaves;
    $leaveDurationWholePeriod += $leaveDuration;
  }

  $stat_result_value[0] = $firstPeriodDate;
  $stat_result_value[1] = $day;
  $stat_result_value[2] = $resultPureDurationWholePeriod; 
  $stat_result_value[3] = $addTimeDurationWholePeriod; 
  $stat_result_value[4] = $lunchDurationWholePeriod; 
  $stat_result_value[5] = 2;
  $stat_result_value[6] = $dayNormWholePeriod;
  $stat_result_value[7] = $PenaltiesDurationWholePeriod;
  $stat_result_value[8] = $PenaltiesCountWholePeriod;
  $stat_result_value[9] = $pauseTimeDurationWholePeriod; 
  $stat_result_value[10] = $dayNormBeforeLeavesWholePeriod;
  $stat_result_value[11] = $leaveDurationWholePeriod;
  $stat_results[] = $stat_result_value;

  $stats = array();
  $stats[] = $days_dates_set;           // 0
  $stats[] = $days_work_start;          // 1
  $stats[] = $days_work_stop;           // 2
  $stats[] = $days_add_infos;           // 3
  $stats[] = $days_add_infos;           // 4
  $stats[] = $days_eat_start;           // 5
  $stats[] = $days_eat_stop;            // 6
  $stats[] = $days_is_there_work_time;  // 7
  $stats[] = $days_day_type;            // 8
  $stats[] = $days_errors;              // 9

  $stats[] = $days_penalties;           // 10
  $stats[] = $days_penalty_reasons;     // 11
  $stats[] = $days_penalty_supervisor;  // 12
  $stats[] = $stat_results;             // 13
  $stats[] = $userDayNorm;              // 14

  $stats[] = $days_day_state;           // 15
  $stats[] = $days_day_currday;         // 16
  $stats[] = $days_penalty_durationVal; // 17

  $stats[] = $days_remoteWorkState;     // 18
  $stats[] = $days_timeZoneSec;         // 19
  $stats[] = $days_dayTransitionTime;   // 20
  $stats[] = $days_leave_events; // 21

  return $stats;
}

function get_report_body_row_contents( $usersInfo ){
  include_once __DIR__ . "/funcs.php";

  $currentDateArr = get_current_datetime_in_timezone();
  $currDate = $currentDateArr[2];

  $rowsDTContent = array();
  $rowsContent = array();

  $index = 0;

  $userCount = count($usersInfo[1]);

  $cellWidth = 165;

  $rowContent = "";
  $rowDTContent = "";

  foreach ( $usersInfo[7][0][0] as $currentMonthDate )
  {
    $currentDayName = GetWeekDayNameD( $currentMonthDate );

    $sufix = "";
    $sufixFound = 0;     

    if ( $currentMonthDate == $currDate )
    {
      $sufixFound = 1;
      $sufix = "текущий день";
    }

    $rowContent = "";
    $rowDTContent = "";

    if ( $sufixFound == 0 )
    {
      $rowDTContent  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
      $rowDTContent .=   "<div class=\"report_head_left_date_cert1\" id=\"report_head_left_date_cert1\">";
      $rowDTContent .=      "<div><h5>$currentMonthDate</h5></div>";
      $rowDTContent .=       "<div><h5>$currentDayName</h5></div>";
      $rowDTContent .=   "</div>"; 
      $rowDTContent .= "</td>";
    }
    else
    {
      $rowDTContent  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
      $rowDTContent .=   "<div class=\"report_head_left_date_cert\" id=\"report_head_left_date_cert\">";
      $rowDTContent .=      "<div><h5>$currentMonthDate</h5></div>";
      $rowDTContent .=       "<div><h5>$currentDayName</h5></div>";
      $rowDTContent .=       "<div><h5 class=\"smallBlue\">$sufix</h5></div>";
      $rowDTContent .=   "</div>"; 
      $rowDTContent .= "</td>";
    } 

    for ( $userNum = 0; $userNum < $userCount; $userNum ++ ){
      if ( !isset($usersInfo[0][$userNum]) || !isset($usersInfo[7][$userNum]) ){
        continue;
      }

      $userID = $usersInfo[0][$userNum];

      $userDefaultStartTime = isset($usersInfo[3][$userNum])
        ? $usersInfo[3][$userNum]
        : "NDF";

      $userAllowedDelay = isset($usersInfo[6][$userNum])
        ? $usersInfo[6][$userNum]
        : 0;

      $cellContent = get_cell_content_by_stat(
        $usersInfo[7][$userNum],
        $index,
        $cellWidth,
        $userID,
        $userDefaultStartTime,
        $userAllowedDelay
      );

      $rowContent .= $cellContent;
    }

    $rowsContent[] = $rowContent;
    $rowsDTContent[] = $rowDTContent;

    $headContent = "";

    for ( $resType = 1; $resType <= 6; $resType ++ )
    {
      $rowResContent = "";
      $typeShowed = 0;

      for ( $userNum = 0; $userNum < $userCount; $userNum ++ )
      {
        $userID = $usersInfo[1][$userNum];
    
        $rowResContent .= get_results_cell_content_by_stat( $usersInfo[7][$userNum], $index, $cellWidth, $userID, $userDefaultStartTime, $userAllowedDelay, $resType, $typeShowed, $headContent );
      }

      if ( $rowResContent != "" )
      {
        $rowsContent[] = $rowResContent; 
                              
        $rowsDTContent[] = $headContent;

      }
    }                                
    $index ++;  
  }     

  return array( $rowsDTContent, $rowsContent );
}
?>
