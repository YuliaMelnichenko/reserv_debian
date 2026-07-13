<?php
auth();

function show_month_stat( $monthDate, $user_id, $user_rate, $user_defaultStartTime, $user_defaultStartHour, $user_defaultStartMinute, $user_allowedDelay )
{  
  $monthNum = (int)GetMonthD( $monthDate );

  $currYear = GetCurrentYearD( $monthDate );
  $currMonth = date("m");
  $newdateFirstMonthDay = $monthDate;
  $newdateLastMonthDay = GetLastMonthDay( $newdateFirstMonthDay );

  $newdateCurrentMonthDay = $newdateLastMonthDay;

  if ( $currMonth == $monthNum )
  {
    $newdateCurrentMonthDay = DayIncDN( GetCurrentDate(), -1 );
  }
    
  $StatMonthName = GetMonthName( $monthNum )." ( $currYear г. )   ";
  $StatMonthNorm = GetHourNormByMonth( $newdateFirstMonthDay, $user_rate );

  $stat = get_stat_by_range( $newdateFirstMonthDay, $newdateLastMonthDay, $user_id, $user_defaultStartTime, $user_allowedDelay );

  $fullWorkDayDurationStr = format_time_d_hhmm_pure( round_to_minute( $stat[1]) );
  $pureWorkDayDurationStr = format_time_d_hhmm_pure( round_to_minute( $stat[2] ) );
  $addTimeWorkDayDurationStr = format_time_d_hhmm_pure( round_to_minute( $stat[3] ) );
  $eatWorkDayDurationStr = format_time_d_hhmm_pure( round_to_minute( $stat[4] ) );

  $normByCurrentMonth = get_norm_by_range_sec( $newdateFirstMonthDay, $newdateCurrentMonthDay, $user_id );
  if ( strtotime( $newdateCurrentMonthDay ) <= strtotime( $newdateFirstMonthDay ) )
    $normByCurrentMonth = 0;
  $normByCurrentMonthAdd = 0;

  if ( $currMonth == $monthNum )
  {
    $normByCurrentMonthAdd = get_norm_time_by_current_day_sec( $user_defaultStartHour, $user_defaultStartMinute );
  }

  $normByCurrentMonth = $normByCurrentMonth + $normByCurrentMonthAdd;

  $currenMonthWorkDuration = $stat[2];

  $currenMonthWorkDurationAdd = 0;

  if ( $currMonth == $monthNum )
  {
    $currenMonthWorkDurationAdd = get_current_day_duration_sec( $user_id, $user_defaultStartTime );
  }

  $currenMonthWorkDuration = $currenMonthWorkDuration + $currenMonthWorkDurationAdd;

  $currenMonthWorkDurationStr = format_time_d_hhmm_pure( $currenMonthWorkDuration );

  $goodColor = "b5fe10";
  $badColor = "ff9696";

  $currentOverloadColor = "eeeeee";
  $currentOverPhrase = "Текущая переработка";
  $isThereCurrentOverload = 1;

  $currentOverloadDiff = 0;

  if( $currenMonthWorkDuration > $normByCurrentMonth )
  {
    $currentOverloadColor = $goodColor;    
    $isThereCurrentOverload = 1;
    $currentOverloadDiff = $currenMonthWorkDuration - $normByCurrentMonth;
  }
  else
  {
    $currentOverloadColor = $badColor;    
    $isThereCurrentOverload = 0;
    $currentOverPhrase = "Текущая недоработка";
    $currentOverloadDiff = $normByCurrentMonth - $currenMonthWorkDuration;
  } 

  $currentOverloadDiff = round_to_minute( $currentOverloadDiff ); 

  $currentOverloadDiffStr = format_time_d_hhmm_pure( $currentOverloadDiff );
  
  if ( $stat[3] == 0 )
  {
    $addTimeWorkDayDurationStr = "---";
  }

  $delayCount = $stat[5];
  $penalty = $delayCount * 1000;
  $delayDurationStr = format_time_d_hhmm_pure( round_to_minute( $stat[6] ) );

  $penaltyColor = "eeeeee";

  if ( $delayCount == 0 )
  {
    $delayCount = "---";
    $penalty = "---";
    $delayDurationStr = "---";
    $penaltyColor = $goodColor;
  }
  else
  {
    $penaltyColor = $badColor;
  }

  $workDuration = $stat[2];  
  $normkDuration = $StatMonthNorm * 60 * 60;

  $workResult = "Недоработка";
  $diff = 0;
  $overLoadColor = "eeeeee";

  if ( $workDuration > $normkDuration )
  {
    $workResult = "Переработка";
    $diff = $workDuration - $normkDuration;
    $overLoadColor = $goodColor;
  }
  else
  {
    $diff = $normkDuration - $workDuration;
    $overLoadColor = $badColor;
  }

  $overTime = format_time_d_hhmm_pure( round_to_minute( $diff ) );

  $isCurrentMonth = 0;
  
  if ( $currMonth == $monthNum )
    $isCurrentMonth = 1; 

  $classByColor = function ($color) use ($goodColor, $badColor) {
    if ($color === $goodColor) return "short-stat-good";
    if ($color === $badColor) return "short-stat-bad";
    return "short-stat-neutral";
  };

  $renderRow = function ($label, $value, $rowClass) {
    echo "<tr class=\"$rowClass\">";
    echo "<td class=\"nopadding_s short-stat-label\">" . html_escape($label) . "</td>";
    echo "<td class=\"nopadding_s short-stat-value\">" . html_escape($value) . "</td>";
    echo "</tr>";
  };

  echo "<table class=\"short-stat-card\">";
  echo "<tr><td class=\"nopadding_s short-stat-card-cell\">";
  echo "<strong>" . html_escape($StatMonthName) . "</strong> ";
  echo "(норма <strong>" . html_escape($StatMonthNorm) . "</strong> ч.):<br>";
  echo "<table class=\"short-stat-table\">";

  $workRowClass = $isCurrentMonth == 0 ? $classByColor($overLoadColor) : "short-stat-neutral";
  $renderRow("Наработка (ч.):", $currenMonthWorkDurationStr, $workRowClass);
  $renderRow("Наработка вне офиса (ч.):", $addTimeWorkDayDurationStr, "short-stat-neutral");

  if ($isCurrentMonth == 0) {
    $renderRow($workResult . " (ч.):", $overTime, $classByColor($overLoadColor));
  } else {
    $renderRow($currentOverPhrase . " (ч.):", $currentOverloadDiffStr, $classByColor($currentOverloadColor));
  }

  $renderRow("Продолжительность обеденного времени (ч.):", $eatWorkDayDurationStr, "short-stat-neutral");
  $penaltyRowClass = $classByColor($penaltyColor);
  $renderRow("Кол-во опозданий по неуважит. причине (шт.):", $delayCount, $penaltyRowClass);
  $renderRow("Общее время опозданий (ч.):", $delayDurationStr, $penaltyRowClass);
  $renderRow("Размер штрафных санкций (руб.):", $penalty, $penaltyRowClass);

  echo "</table></td></tr></table>";
}
?>
