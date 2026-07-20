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

  $ColumnSizeBase = 550;
  $firstColumnSize = 450;
  $secondColumnSize = $ColumnSizeBase - $firstColumnSize;

  //////
  echo "<table class=\"slim\" border=1>";
    echo "<tr>";
      echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 600>";
        echo "<b><font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($StatMonthName) . " </font></b>";
        echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">(норма <b>" . html_escape($StatMonthNorm) . "</b> ч.):</font><br>";
        //////
  echo "<table cellpadding=\"0\" cellspacing=\"0\" border=1>";
        if ( $isCurrentMonth == 0 )
            echo "<tr bgcolor=\"#$overLoadColor\" >";
          else
          echo "<tr bgcolor=\"#eeeeee\" >";

             echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $firstColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">Наработка (ч.):</font><br>";
      echo "</td>";
             echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"right\" width = $secondColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($currenMonthWorkDurationStr) . "</font><br>";
      echo "</td>";
    echo "</tr>";
        echo "<tr>";
             echo "<td class=\"nopadding_s\" bgcolor=\"#eeeeee\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $firstColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">Наработка вне офиса (ч.):</font><br>";
      echo "</td>";
             echo "<td class=\"nopadding_s\" bgcolor=\"#eeeeee\" bordercolor=\"#888888\" valign=\"top\" align=\"right\" width = $secondColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($addTimeWorkDayDurationStr) . "</font><br>";
      echo "</td>";
    echo "</tr>";
          if ( $isCurrentMonth == 0 )
        {
            echo "<tr bgcolor=\"#$overLoadColor\" >";
               echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $firstColumnSize>";
                echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($workResult) . " (ч.):</font><br>";
        echo "</td>";
               echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"right\" width = $secondColumnSize>";
                echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($overTime) . "</font><br>";
        echo "</td>";
      echo "</tr>";
          }
          else
        {
            echo "<tr bgcolor=\"#$currentOverloadColor\" >";
               echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $firstColumnSize>";
                echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($currentOverPhrase) . " (ч.):</font><br>";
        echo "</td>";
               echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"right\" width = $secondColumnSize>";
                echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($currentOverloadDiffStr) . "</font><br>";
        echo "</td>";
      echo "</tr>";
          }
        echo "<tr>";
             echo "<td class=\"nopadding_s\" bgcolor=\"#eeeeee\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $firstColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">Продолжительность обеденного времени (ч.):</font><br>";
      echo "</td>";
             echo "<td class=\"nopadding_s\" bgcolor=\"#eeeeee\" bordercolor=\"#888888\" valign=\"top\" align=\"right\" width = $secondColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($eatWorkDayDurationStr) . "</font><br>";
      echo "</td>";
    echo "</tr>";
          echo "<tr bgcolor=\"#$penaltyColor\" >";
            echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $firstColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">Кол-во опозданий по неуважит. причине (шт.):</font><br>";
      echo "</td>";
            echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"right\" width = $secondColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($delayCount) . "</font><br>";
      echo "</td>";
    echo "</tr>";
          echo "<tr bgcolor=\"#$penaltyColor\" >";
            echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $firstColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">Общее время опозданий (ч.):</font><br>";
      echo "</td>";
            echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"right\" width = $secondColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($delayDurationStr) . "</font><br>";
      echo "</td>";
    echo "</tr>";
          echo "<tr bgcolor=\"#$penaltyColor\" >";
            echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $firstColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">Размер штрафных санкций (руб.):</font><br>";
      echo "</td>";
            echo "<td class=\"nopadding_s\" bordercolor=\"#888888\" valign=\"top\" align=\"right\" width = $secondColumnSize>";
              echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">" . html_escape($penalty) . "</font><br>";
      echo "</td>";
    echo "</tr>";
        echo "</table>";
  //////
      echo "</td>";
    echo "</tr>";
  echo "</table>";
}
?>
