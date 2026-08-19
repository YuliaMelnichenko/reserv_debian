<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/work_calendar.php';
require_once __DIR__ . '/work_duration.php';
require_once __DIR__ . '/project_database.php';

function get_penalties( $userDays, $userID )
{
  $maxDate = max_date( $userDays );
  $minDate = min_date( $userDays );

  $penalties = array();
  $idx = 1;
  $link = project_database_connect();

  $query = db_query($link, "SELECT date from Penalty where date >= ? and date <= ? and userID = ?", 'ssi', array($minDate, $maxDate, (int)$userID));
  $merr = db_error($link);
  if ( !$query )
  {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else
  {
    while ( $row = db_fetch_one($query) )
    {
      $penaltyDate = $row["date"];

      for ( $idx2 = 1; $idx2 <= count( $userDays ); $idx2 ++ )
      {
        if ( $userDays[$idx2] == $penaltyDate )
        {
          $penalties[$idx] = $penaltyDate;
          $idx ++;
          break;
        }
      }
    }
  }
  return $penalties;
}

function get_current_day_duration_sec( $userID, $defaultStartTime ){
  $link = project_database_connect();

  $currentDateTime = get_current_datetime_in_timezone()[1];
  $dateRange = datetimestr_to_day_start_stop_DT_ex_str($currentDateTime, '00:00:00');

  $query = db_query($link, "
    SELECT in_dt
    FROM visiting
    WHERE user_id = ?
      AND state != 0
      AND in_dt >= ?
      AND in_dt <= ?
    ORDER BY in_dt DESC, ID DESC
    LIMIT 1
  ", 'iss', array((int)$userID, $dateRange[0], $dateRange[1]));

  if (!$query) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    return 0;
  }

  $row = db_fetch_one($query);

  if (!$row) {
    return 0;
  }

  return get_defined_time_range_duration($row["in_dt"], $currentDateTime);
}

function get_stat_by_range( $startDate, $stopDate, $userID, $user_defaultStartTime, $user_allowedDelay ){
  $link = project_database_connect();

  $holidays = get_holidays();
  $workDays = get_work_day();
  $daysRange = get_days_range($startDate, $stopDate);
  $penaltyDates = get_penalties($daysRange, $userID);

  $add_time_work_dayduration = 0;
  $full_work_day_duration = 0;
  $eat_work_day_duration = 0;
  $pure_work_day_duration = 0;
  $delay_count = 0;
  $delay_duration = 0;

  $periodStart = $startDate . ' 00:00:00';
  $periodStop = date('Y-m-d 00:00:00', strtotime($stopDate . ' +1 day'));
  $query1 = time_journal_query_approved_add_time($link, $userID, $periodStart, $periodStop);

  if (!$query1) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else {
    while ($row1 = db_fetch_one($query1)) {
      $clippedRange = clip_datetime_range($row1['START_DT'], $row1['STOP_DT'], $periodStart, $periodStop);

      if ($clippedRange !== null) {
        $add_time_work_dayduration += $clippedRange['duration'];
      }
    }
  }

  $query2 = db_query($link, "
    SELECT in_dt, out_dt, eat_start_dt, eat_stop_dt
    FROM visiting
    WHERE in_dt >= ?
      AND in_dt < ?
      AND user_id = ?
      AND state = 0
      AND out_dt > in_dt
    ORDER BY in_dt
  ", 'ssi', array($periodStart, $periodStop, (int)$userID));

  if (!$query2) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else {
    while ($row2 = db_fetch_one($query2)) {
      $visitStat = get_completed_visit_statistics(
        $row2["in_dt"],
        $row2["out_dt"],
        $row2["eat_start_dt"],
        $row2["eat_stop_dt"],
        $user_defaultStartTime,
        $user_allowedDelay
      );

      if ($visitStat === null) {
        continue;
      }

      $date = $visitStat['date'];
      $takeIntoAccount = !isWeekEnd($date)
        ? !in_array($date, $holidays, true)
        : in_array($date, $workDays, true);

      if (
        $takeIntoAccount
        && $visitStat['delay_duration'] > 0
        && in_array($date, $penaltyDates, true)
      ) {
        $delay_count++;
        $delay_duration += $visitStat['delay_duration'];
      }

      $full_work_day_duration += $visitStat['full_duration'];
      $eat_work_day_duration += $visitStat['lunch_duration'];
      $pure_work_day_duration += $visitStat['pure_duration'];
    }
  }

  return array(
    1 => $full_work_day_duration,
    2 => $pure_work_day_duration,
    3 => $add_time_work_dayduration,
    4 => $eat_work_day_duration,
    5 => $delay_count,
    6 => $delay_duration,
  );
}
