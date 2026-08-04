<?php

require_once __DIR__ . '/output.php';
require_once __DIR__ . '/time_format.php';
require_once __DIR__ . '/work_duration.php';
require_once __DIR__ . '/calendar.php';
require_once __DIR__ . '/date_range.php';

function redmine_represent( $timeIn )
{

$timeInSrc = $timeIn;

  $hours = floor($timeIn / 3600);
  $timeIn = $timeIn - $hours * 3600;

  $minutes = round( $timeIn / 3600, 3 );

  $hoursStr = (string)$hours;

  $minutesStr = (string)$minutes;

  $minutesStrLen = strlen( $minutesStr );

  $minutesStr = substr( $minutesStr, 2, 2 );

  $result = $hoursStr.".".$minutesStr;

  return $result;
}

function get_results_cell_content_by_stat( $stats, $index, $cellWidth, $userID, $defaultStartTimeStr, $user_allowedDelay, $resType, &$typeShowed, &$headContent )
{
  $days_dates_set = $stats[0][$index];
  // echo $stats[0][0];

  $days_dates_results = $stats[13];


  $new_days_dates_set = DayIncDN( $days_dates_set, 1 );

  $contentDT = "";
  $content = "";

  foreach( $days_dates_results as $results )
  {
    if ( $results[1] == $new_days_dates_set AND $results[5] == $resType )
    {
      if ( $typeShowed == 0 )
      {
        $typeShowed = 1;

        if ( $resType == 1 OR $resType == 2 )
        {
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
          $contentDT .=   "<div class=\"report_head_left_date_rep_period\" id=\"report_head_left_date_rep_period\">";
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за период:<br></h5>";
          $contentDT .=     "<h6 class=\"mism1\"><br>$results[0]<br>-<br>$results[1]</h6>";
          $contentDT .=   "</div>";
          $contentDT .= "</td>";
          $headContent = $contentDT;
        }

        else if ( $resType == 3 )
        {
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
          $contentDT .=   "<div class=\"report_head_left_date_rep_week\" id=\"report_head_left_date_cert\">";
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за<br>неделю";
          $contentDT .=   "</div>";
          $contentDT .= "</td>";
          $headContent = $contentDT;
        }
        else if ( $resType == 4 ) {
          $monthName = GetMonthNameByDate( $days_dates_set );
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
          $contentDT .=   "<div class=\"report_head_left_date_rep_month\" id=\"report_head_left_date_cert\">";
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за<br>месяц:<br><h6 class=\"mism1\">".$monthName;
          $contentDT .=   "</div>";
          $contentDT .= "</td>";
          $headContent = $contentDT;
        }
        else if ( $resType == 5 )
        {
          $QuarterNum = GetQuarterRomNumByDate( $days_dates_set );
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
          $contentDT .=   "<div class=\"report_head_left_date_rep_quarter\" id=\"report_head_left_date_cert\">";
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за<br><h6 class=\"mism1\">$QuarterNum<h5 class=\"smallBlack\">квартал";
          $contentDT .=   "</div>";
          $contentDT .= "</td>";
          $headContent = $contentDT;
        }
        else if ( $resType == 6 )
        {
          $YearNum = GetCurrentYearD( $days_dates_set );
          $contentDT  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
          $contentDT .=   "<div class=\"report_head_left_date_rep_year\" id=\"report_head_left_date_cert\">";
          $contentDT .=     "<h5 class=\"smallBlack\">Итог за<br><h6 class=\"mism1\">$YearNum<h5 class=\"smallBlack\">год";
          $contentDT .=   "</div>";
          $contentDT .= "</td>";
          $headContent = $contentDT;
        }
      }

      if ( $results[2] < $results[6]  )
      {
	$resultColor = "#FFDDDD";
        $timeSpendImg = "img/workTimeBad.png";
        $lunchImg = "img/lunchTimeBad.png";
        $addTimeImg = "img/AddworkTimeBad.png";
        $pauseTimeImg = "img/PauseTimeBad.png";
        $addTimeListImg = "img/AddworkTimeListBad.png";
        $penaltyImg = "img/PenaltyBad.png";
        $overloadImg = "img/OverloadgBad.png";
        $overloadTitle = "недоработка до нормы за указанный интервал времени";
        $normImg = "img/NormBad.png";
        $overloadAbsolute = $results[6] - $results[2];
      }
      else
      {
	$resultColor = "#DDFFDD";
        $timeSpendImg = "img/workTimeGood.png";
        $lunchImg = "img/lunchTimeGood.png";
        $addTimeImg = "img/AddworkTimeGood.png";
        $pauseTimeImg = "img/PauseTimeGood.png";
        $addTimeListImg = "img/AddworkTimeListGood.png";
        $penaltyImg = "img/PenaltyGood.png";
        $overloadImg = "img/OverloadGood.png";
        $overloadTitle = "переработка сверх нормы за указанный интервал времени";
        $normImg = "img/NormGood.png";
        $overloadAbsolute = $results[2] - $results[6];
      }

      $content .= "<td class=\"report_no_padding\" bgcolor=\"$resultColor\" bordercolor=\"#888888\" valign=\"top\" align=\"left\">";
      $content .=   "<div class=\"report_body_head_summary\" id=\"report_body_head_summary\">";

      $content .=            "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $content .=                  "<img title=\"фактическая наработка за указанный интервал времени без учета обеда\" src=\"$timeSpendImg\"/>";
      $content .=                "</div>";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $Val2 = (int)($results[2]);
      $Val4 = (int)($results[4]);
      $Val3 = (int)($results[3]);
      $Val9 = (int)($results[9]);

$Val = $Val2 + $Val4 - $Val3 + $Val9;
      $content .=                  format_time_d_hhmmss_pure_styled( $Val );

      $content .=                "</div>";

      $content .=          "</div>";

      $content .=            "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $content .=                  "<img title=\"обеденное время за указанный интервал времени\" src=\"$lunchImg\"/>";
      $content .=                "</div>";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $Val = (int)($results[4]);
      $content .=                  format_time_d_hhmmss_pure_styled( $Val );

      $content .=                "</div>";
      $content .=            "</div>";

      $content .=            "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 5px>";
      $content .=                  "<img title=\"рабочее время вне офиса за указанный интервал времени\" src=\"$addTimeImg\"/>";
      $content .=                "</div>";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $Val = (int)($results[3]);
      $content .=                  format_time_d_hhmmss_pure_styled( $Val );

      $content .=                "</div>";
      $content .=            "</div>";

      $content .=            "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\">";
      $content .=                  "<img title=\"приостановки учета времени за указанный интервал времени\" src=\"$pauseTimeImg\"/>";
      $content .=                "</div>";
      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 8px>";
      $Val = (int)($results[9]);
      $content .=                  format_time_d_hhmmss_pure_styled( $Val );

      $content .=                "</div>";
      $content .=            "</div>";

      $content .=        "<div class = \"time_rep\">";

      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 3px>";
      $content .=                  "<img title=\"штрафные санкции за опоздание по неуважительной причине за указанный интервал времени\" src=\"$penaltyImg\"/>";
      $content .=                "</div>";

      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 152px>";
      $Val = (int)($results[7]);
      $ValC = (int)($results[8]);
      $ValP = $ValC * 1000;
      $content .=                 format_time_d_hhmmss_pure_styled( $Val );
      if ($ValC > 0)
      {
        $content .=                  "<h3 class=\"small1\"> [".(string)$ValC."x1000 = ".$ValP."р]</h3>";
      }
      $content .=                "</div>";
      $content .=        "</div>";

      $content .=        "<div class = \"result\">";
      $content .=             "<h5 class=\"middleSmall\">Итог:</h5>";

      $content .=        "</div>";

      $content .=        "<div class = \"time_rep\">";
      $content .=                "<div class = \"report_no_padding_rep\" align = \"left\" width = 10px>";
      $ValNormBeforeLeaves = isset($results[10]) ? (int)($results[10]) : (int)($results[6]);
      $ValLeaveHours = isset($results[11]) ? (int)($results[11]) : 0;
      $ValNormAfterLeaves = (int)($results[6]);
      $ValFact = (int)($results[2]);

      $ValRedmine = redmine_represent($ValFact);

      $content .= "<h5 class=\"middle\" title=\"Норма часов за период с учетом выходных, праздников и предпраздничных дней, но без вычета отпуска и больничного\"> "
        . format_time_d_hhmmss_pure_HH($ValNormBeforeLeaves)
        . " - Норма (ч.)</h5></br>";

      if ($ValLeaveHours > 0) {
        $content .= "<h5 class=\"middle\" title=\"Количество часов отпуска и больничного, вычтенное из нормы за выбранный период\"> "
          . format_time_d_hhmmss_pure_HH($ValLeaveHours)
          . " - Отсутствие (ч.)</h5></br>";
      }

      $content .= "<h5 class=\"middle\" title=\"Норма часов к отработке после вычета отпуска и больничного\"> "
        . format_time_d_hhmmss_pure_HH($ValNormAfterLeaves)
        . " - К отработке (ч.)</h5></br>";

      $content .= "<span title=\"Фактически отработанное время за выбранный период\">"
        . format_time_d_hhmmss_pure_styled($ValFact)
        . "(" . $ValRedmine . ")"
        . "</span><h5 class=\"middle\" title=\"Фактически отработанное время за выбранный период\"> - Факт </h5>";      $content .=        "</div>";

      $content .=      "</div>";

     $content .=   "</div>";
     $content .= "</td>";
    }
  }

  return $content;
}
