<?php

require_once __DIR__ . '/output.php';
require_once __DIR__ . '/time_format.php';
require_once __DIR__ . '/work_duration.php';
require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/date_range.php';

function get_cell_content_by_stat( $stats, $index, $cellWidth, $userId, $defaultStartTimeStr, $user_allowedDelay ){
  $delayCheckEnabled = 1;

  if (
    !isset($defaultStartTimeStr) ||
    $defaultStartTimeStr == "" ||
    $defaultStartTimeStr == "NDF" ||
    strtotime($defaultStartTimeStr) === false
  ) {
    $delayCheckEnabled = 0;
    $defaultStartTimeStr = "NDF";
    $user_allowedDelay = 0;
  }

  if (!isset($user_allowedDelay) || $user_allowedDelay == "" || !is_numeric($user_allowedDelay)) {
    $user_allowedDelay = 0;
  }

  $user_allowedDelay = (int)$user_allowedDelay;

  // $dayTypes = get_workdays_holidays_bay_range( $startDate, $stopDate );
  $currentDateArr = get_current_datetime_in_timezone();
  $currentDate = $currentDateArr[2];

  $days_dates_set = $stats[0][$index];
  $days_work_start = $stats[1][$index];
  $days_work_stop = $stats[2][$index];
  $days_add_info = $stats[3][$index];
  $days_eat_start = $stats[5][$index];
  $days_eat_stop = $stats[6][$index];
  $days_day_type = $stats[8][$index];

  $days_penalties = $stats[10][$index];

  $days_day_state = $stats[15][$index];
  $days_day_currday = $stats[16][$index];
  $days_day_delay_duration = $stats[17][$index];

  $days_remoteWorkState = $stats[18][$index];
  $days_timeZoneSec = $stats[19][$index];
  $days_dayTransitionTime = $stats[20][$index];

  $days_leave_event = "NDF";

  if (isset($stats[21][$index])) {
    $days_leave_event = $stats[21][$index];
  }

  $isStaffLeave = 0;

  if ($days_leave_event == "Отпуск" || $days_leave_event == "Больничный") {
    $isStaffLeave = 1;
  }

  $days_timeZoneStr = timezone_min_to_str( $days_timeZoneSec );

  $isCurrentDay = 0;
  $notCurrentDay = 1;
  if ( $currentDate == $days_dates_set )
  {
    $isCurrentDay = 1;
    $notCurrentDay = 0;
  }

  $dayNorm = 8 * 60 * 60;
  $eatNorm = 1 * 60 * 60;

  $errorDur = 0;

  $changesArr = is_there_day_change_betw( $days_work_start, $days_eat_start, $days_eat_stop, $days_work_stop, $days_day_state );
  $durations = get_durations( $days_work_start, $days_work_stop, $days_eat_start, $days_eat_stop, $days_add_info, $days_day_state, $days_day_currday );
  $crossDayPeriod = 0;//$changesArr[4];

  if ( $currentDate == $days_dates_set )
  {
    $isCurrentDay = 1;
    $notCurrentDay = 0;
  }

  $isWeekend = isWeekEnd( $days_dates_set );
  $isholiday = 0;
  if ( $days_day_type >= 100 AND $days_day_type < 200 )
  {
    $isholiday = 1;
  }

  $isworkForceday = 0;
  if ( $days_day_type >= 200 )
  {
    $isworkForceday = 1;
  }

  $commonChechState = 1;
  $commonEatChechState = 1;
  if ( $isCurrentDay == 1 )
  {
    $commonChechState = 0;
  }
  else
  {
    if ( $isWeekend == 1 )
    {
       if ( $isworkForceday == 0 )
       {
         $commonChechState = 0;
         $commonEatChechState = 0;
       }
    }
    else
    {
      if ( $isholiday == 1 )
      {
        $commonChechState = 0;
        $commonEatChechState = 0;
      }
    }
  }


  $workWOEat = $durations[0];

  $workWOEatStr = colored_result( "", $workWOEat, $dayNorm, 0, $commonChechState, 0 );

  $resultTime = $durations[3];
  $resultTimeStr = colored_result( "", $resultTime, $dayNorm, 0, $commonChechState, 0 );

  $lunchDuration = $durations[1];
  $lunchDurationStr = colored_result( "", $lunchDuration, $eatNorm, 1, $commonEatChechState, 0 );

  $addTimeDuration = $durations[2];
  $addTimeDurationStr = colored_result( "", $addTimeDuration, 0, 0, 0, 0 );

  $pauseTimeDuration = $durations[5];
  $pauseTimeDurationStr = colored_result( "", $pauseTimeDuration, 0, 0, 0, 0 );

  $penaltyDuration = $days_day_delay_duration;
  $penaltyDurationStr = "";

  if ( $days_penalties == 1 )
  {
    $penaltyDurationStr = format_time_d_hhmmss_pure( $penaltyDuration );
  }

  $resultPureTime = $durations[3];

  $needCheck = $notCurrentDay;

  if ( $currentDate == $days_dates_set AND is_time_defined( $days_work_stop ) == 1 )
  {
    $needCheck = 1;
  }

  if ( $isWeekend OR $isholiday ){
    $needCheck = 0;
  }

  if ($isStaffLeave == 1) {
    $needCheck = 0;
  }

  $resultPureTimeStr = colored_result( "", $resultTime, $dayNorm, 0, $needCheck, 1 );
  $resultPureTimePartStr = colored_result_partial( "", $resultTime, $dayNorm, 0, $needCheck, 1 );


  $dayColor = "#DDFFDD";
  $timeSpendImg = "img/workTimeGood.png";
  $lunchImg = "img/lunchTimeGood.png";
  $addTimeImg = "img/AddworkTimeGood.png";
  $pauseTimeImg = "img/PauseTimeGood.png";
  $addTimeListImg = "img/AddworkTimeListGood.png";
  $penaltyImg = "img/PenaltyGood.png";
  $remoteWorkImg = "img/remoteWorkGood.png";
  $cellaligment = "left";

  if ( $dayNorm > $resultTime )
  {
    $dayColor = "#FFDDDD";
    $timeSpendImg = "img/workTimeBad.png";
    $lunchImg = "img/lunchTimeBad.png";
    $addTimeImg = "img/AddworkTimeBad.png";
    $pauseTimeImg = "img/PauseTimeBad.png";
    $addTimeListImg = "img/AddworkTimeListBad.png";
    $penaltyImg = "img/PenaltyBad.png";
    $remoteWorkImg = "img/remoteWorkBad.png";
  }

  if ( $currentDate == $days_dates_set OR $isWeekend OR $isholiday )
  {
    $dayColor = "#ddeeff";
    $timeSpendImg = "img/workTimeCur.png";
    $lunchImg = "img/lunchTimeCur.png";
    $addTimeImg = "img/AddworkTimeCur.png";
    $pauseTimeImg = "img/PauseTimeCur.png";
    $addTimeListImg = "img/AddworkTimeListCur.png";
    $penaltyImg = "img/PenaltyCur.png";
    $remoteWorkImg = "img/remoteWorkCur.png";
  }

  if ( $isWeekend OR $isholiday ){
    $dayColor = "#C1CDC4";
    $timeSpendImg = "img/workTimeGood.png";
    $lunchImg = "img/lunchTimeGood.png";
    $addTimeImg = "img/AddworkTimeGood.png";
    $pauseTimeImg = "img/PauseTimeGood.png";
    $addTimeListImg = "img/AddworkTimeListGood.png";
    $penaltyImg = "img/PenaltyGood.png";
    $remoteWorkImg = "img/remoteWorkGood.png";
    $cellaligment = "left";
  }

  if ($isStaffLeave == 1){
    $dayColor = "#C1CDC4";
    $timeSpendImg = "img/workTimeGood.png";
    $lunchImg = "img/lunchTimeGood.png";
    $addTimeImg = "img/AddworkTimeGood.png";
    $pauseTimeImg = "img/PauseTimeGood.png";
    $addTimeListImg = "img/AddworkTimeListGood.png";
    $penaltyImg = "img/PenaltyGood.png";
    $remoteWorkImg = "img/remoteWorkGood.png";
    $cellaligment = "left";
  }


  $workDayRange = "";
  {
    $workDayRange = get_range_by_times_pair( $days_work_start, $days_work_stop, $isCurrentDay, $commonChechState, $defaultStartTimeStr, $user_allowedDelay, $crossDayPeriod );
  }

  $eatRange = "";
  {
    $eatRange = get_range_by_times_pair( $days_eat_start, $days_eat_stop, $isCurrentDay, 0, $defaultStartTimeStr, $user_allowedDelay, $crossDayPeriod );
  }

  $valignMode = "bottom";

  $isThereData = 1;


  $noDataStr = "Нет сведений!";
  $noDataStyle = "middleBoldRed";

  if ( $isWeekend AND ! $isholiday ){
    $noDataStr = "Выходной день";
    $noDataStyle = "middleBoldGreen";
  }
  else if ( $isholiday ){
    $noDataStr = "Праздничный день!";
    $noDataStyle = "middleBoldGreen";
  }
  else if ($isStaffLeave == 1){
    $noDataStr = $days_leave_event;
    $noDataStyle = "middleBoldGreen";
  }

  $prefix = "";

  if ( $days_dates_set == $currentDate )
  {
    if ( is_time_defined( $days_work_start ) == 0
      && is_time_defined( $days_work_stop ) == 0
      && is_time_defined( $days_eat_start ) == 0
      && is_time_defined( $days_eat_stop ) == 0
      && $addTimeDuration == 0
      && $pauseTimeDuration == 0
      && $penaltyDuration == 0 )
    {
      $prefix = "<h5 class=\"" . $noDataStyle . "\">" . $noDataStr;
      $isThereData = 0;
    }
    else
    {
      $prefix = "<h5 class=\"middleBold\">Текущий день";
    }
  }
  else
  {
    if ( is_time_defined( $days_work_start ) == 0
      && is_time_defined( $days_work_stop ) == 0
      && is_time_defined( $days_eat_start ) == 0
      && is_time_defined( $days_eat_stop ) == 0
      && $addTimeDuration == 0
      && $pauseTimeDuration == 0
      && $penaltyDuration == 0 )
    {
      $prefix = "<h5 class=\"" . $noDataStyle . "\">" . $noDataStr;
      $isThereData = 0;
    }
  }

  $uidWork = 'u' . $userId . '-' . $days_dates_set . '-work';
  $uidLunch= 'u' . $userId . '-' . $days_dates_set . '-lunch';

  $outTimeEmpty = "";

  $workPureContent = "<h5 class=\"bigbig\">$resultPureTimeStr ($resultPureTimePartStr)</h5>";

  if ( $currentDate != $days_dates_set AND $errorDur == 1 )
  {
    $prefix = "<h5 class=\"bigmiddleRed\">Недостаточно сведений!";
  }

  if ( $isThereData == 1 )
  {
    $tableContent =   "<div class = \"right_table\">";
    $tableContent .=     "<div class = \"current_day\">";

    $tableContent .=       "<div class = \"report_no_padding_rep\">";
    $tableContent .=         $prefix;
    $tableContent .=       "</div>";
    if ($prefix == "<h5 class=\"middleBold\">Текущий день"){
      $tableContent .=       "<div class = \"report_no_padding_rep\">";
      $tableContent .=         "<h5 class=\"middleSmall\">$days_timeZoneStr</h5>";
      $tableContent .=       "</div>";
    }
    $tableContent .=      "</div>";


    $tableContent .=   "<div class = \"special_time_rep\">";
    $tableContent .=       "<div class = \"work_time_rep\">";
    $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"left\" width = 10px>";
    $tableContent .=             "<img title=\"рабочее время\" src=\"$timeSpendImg\"/>";
    $tableContent .=           "</div>";
      $tableContent .=          "<div class = \"report_no_padding_rep inf\" data-tooltip=\"$uidWork\" valign = \"top\" align = \"left\" width = 50px>";
      $tableContent .=            $workWOEatStr;
      $tableContent .=          "</div>";
    $tableContent .=        "</div>";
    $tableContent .=        "<div class=\"divs_layer\">";
    $tableContent .=          "<div class = \"report_no_padding_rep time\" data-tooltip-target=\"$uidWork\" align = \"center\" width = 230px>";
    $tableContent .=            $workDayRange;
    $tableContent .=          "</div>";
    $tableContent .=        "</div>";
    $showLegacyRemoteWorkStateInReport = 0;

    if ( $showLegacyRemoteWorkStateInReport == 1 && $days_remoteWorkState != 0 && $days_remoteWorkState != "NDF" ){
      $tableContent .=          "<div class = \"remote_work_time_rep\">";
      $tableContent .=              "<div class = \"report_no_padding_rep\" width = 15px>";
      $tableContent .=                  "<img title=\"удаленный режим работы\" src=\"$remoteWorkImg\"/>";
      $tableContent .=              "</div>";
      $tableContent .=              "<div class = \"report_no_padding_rep\" width = 45px>";
      $tableContent .=                  "<h5 class=\"middle\">(".$days_dayTransitionTime.")</h5>";
      $tableContent .=              "</div>";
      $tableContent .=           "</div>";
    }
    $tableContent .=   "</div>";

    $tableContent .=   "<div class = \"time_rep\">";
    $tableContent .=       "<div class = \"work_time_rep\">";
    $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 10px>";
    $tableContent .=             "<img title=\"обеденное время\" src=\"$lunchImg\"/>";
    $tableContent .=           "</div>";

      $tableContent .=           "<div class = \"report_no_padding_rep inf\" data-tooltip=\"$uidLunch\" align = \"left\" width = 50px>";
      $tableContent .=             $lunchDurationStr;
      $tableContent .=           "</div>";

    $tableContent .=      "</div>";
    $tableContent .=      "<div class=\"divs_layer\">";
    $tableContent .=        "<div class = \"report_no_padding_rep time\" data-tooltip-target=\"$uidLunch\" align = \"center\" width = 230px>";
    $tableContent .=          $eatRange;
    $tableContent .=        "</div>";
    $tableContent .=      "</div>";
    $tableContent .=   "</div>";

    $tableContent .=   "<div class = \"time_rep\">";

    if ( $addTimeDuration != 0 )
    {
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 15px>";
      $tableContent .=             "<img title=\"рабочее время вне офиса\" src=\"$addTimeImg\"/>";
      $tableContent .=           "</div>";
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"left\" width = 40px>";
      $tableContent .=             $addTimeDurationStr;
      $tableContent .=           "</div>";
    }
    else
    {
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"left\" width = 40px style = \"display: none\">";
      $tableContent .=             "<img title=\"рабочее время вне офиса\" src=\"$addTimeImg\">";
      $tableContent .=             "<h5 class=\"middleGrey\">(__:__:__)</h5>";
      $tableContent .=           "</div>";
    }
    $tableContent .=   "</div>";

    if ( $pauseTimeDuration != 0 )
    {
      $tableContent .=   "<div class = \"time_rep\">";
      $tableContent .=     "<div class = \"report_no_padding_rep\" align = \"center\" width = 10px>";
      $tableContent .=        "<img title=\"продолжительность приостановки учета времени\" src=\"$pauseTimeImg\"/>";
      $tableContent .=     "</div>";
      $tableContent .=     "<div class = \"report_no_padding_rep\" align = \"left\" width = 100px>";
      $tableContent .=        $pauseTimeDurationStr;
      $tableContent .=     "</div>";
      $tableContent .=   "</div>";
    }
    else
    {
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 60px style = \"display: none\">";
      $tableContent .=             "<img title=\"продолжительность приостановки учета времени\" src=\"$pauseTimeImg\">";
      $tableContent .=             "<h5 class=\"middleGrey\">(__:__:__)</h5>";
      $tableContent .=           "</div>";
    }

    if ( $penaltyDuration != 0 )
    {
    $tableContent .=   "<div class = \"time_rep\">";
    $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 15px>";
    $tableContent .=             "<img title=\"штрафные санкции за опоздание по неуважительной причине\" src=\"$penaltyImg\"/>";
    $tableContent .=           "</div>";
    $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 60px>";
    $tableContent .=             $penaltyDurationStr;
    $tableContent .=           "</div>";
    $tableContent .=        "</div>";
    }
    else
    {
      $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"center\" width = 60px style = \"display: none\">";
      $tableContent .=             "<img title=\"штрафные санкции за опоздание по неуважительной причине\" src=\"$penaltyImg\" >";
      $tableContent .=             "<h5 class=\"middleGrey\">(__:__:__)</h5>";
      $tableContent .=           "</div>";
    }
  $tableContent .=   "</div>";


  $tableContent .=   "<div class = \"result_time\">";
  $tableContent .=       "<div class = \"time_rep\">";
  $tableContent .=           "<div class = \"report_no_padding_rep\" align = \"left\">";
  $tableContent .=               "$workPureContent";
  $tableContent .=           "</div>";
  $tableContent .=       "</div>";
  $tableContent .=  "</div>";


  $tableContent .= "</div>";
  }
  else
  {
    $tableContent  = "<table>";
    $tableContent .=   "<tr height = 95>";
    $tableContent .=     "<td width = 154px class = \"report_no_padding_rep\" align = center valign = middle>";
    $tableContent .=       $prefix;
    $tableContent .=     "</td>";
    $tableContent .=   "</tr>";
    $tableContent .= "</table>";
  }

  $unformattedContent1 = $prefix;
  // $unformattedContent2 = $workContent.$eatContent.$addTimeContent.$pauseTimeContent.$PenaltyContent.$workPureContent;

   if ($prefix == "<h5 class=\"middleBold\">Текущий день"){
   $content  = "<td class=\"report_no_padding\" bgcolor=\"$dayColor\" bordercolor=\"#888888\" valign=\"$valignMode\" align=\"$cellaligment\" width = $cellWidth>";
   $content .=   "<div class=\"report_body_head_day\" id=\"report_body_head_day\">";
     //$content .=     "$unformattedContent1$unformattedContent2";
   $content .=     "$tableContent";
   $content .=   "<div>";
   $content .= "</td>";
   }
   else {
    $content  = "<td class=\"report_no_padding\" bgcolor=\"$dayColor\" bordercolor=\"#888888\" valign=\"top\" align=\"$cellaligment\" width = $cellWidth>";
    $content .=   "<div class=\"report_body_head_day_first\" id=\"report_body_head_day_first\">";
    $content .=     "$tableContent";
    $content .=   "<div>";
    $content .= "</td>";
   }


  return $content;
}
