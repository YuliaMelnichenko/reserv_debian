<?php

function get_report_user_context($usersInfo, $userNum)
{
  if (!isset($usersInfo[0][$userNum]) || !isset($usersInfo[7][$userNum])) {
    return null;
  }

  return array(
    'id' => $usersInfo[0][$userNum],
    'stats' => $usersInfo[7][$userNum],
    'default_start_time' => isset($usersInfo[3][$userNum]) ? $usersInfo[3][$userNum] : "NDF",
    'allowed_delay' => isset($usersInfo[6][$userNum]) ? $usersInfo[6][$userNum] : 0,
  );
}

function get_report_body_row_contents($usersInfo)
{
  include_once dirname(__DIR__) . "/funcs.php";

  $currentDateArr = get_current_datetime_in_timezone();
  $currDate = $currentDateArr[2];

  $rowsDTContent = array();
  $rowsContent = array();
  $index = 0;
  $userCount = count($usersInfo[1]);
  $cellWidth = 165;

  foreach ($usersInfo[7][0][0] as $currentMonthDate) {
    $currentDayName = GetWeekDayNameD($currentMonthDate);
    $isCurrentDay = ($currentMonthDate == $currDate);

    if (!$isCurrentDay) {
      $rowDTContent  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
      $rowDTContent .=   "<div class=\"report_head_left_date_cert1\" id=\"report_head_left_date_cert1\">";
      $rowDTContent .=      "<div><h5>$currentMonthDate</h5></div>";
      $rowDTContent .=      "<div><h5>$currentDayName</h5></div>";
      $rowDTContent .=   "</div>";
      $rowDTContent .= "</td>";
    }
    else {
      $rowDTContent  = "<td class=\"report_no_padding\" valign=\"middle\" align=\"center\">";
      $rowDTContent .=   "<div class=\"report_head_left_date_cert\" id=\"report_head_left_date_cert\">";
      $rowDTContent .=      "<div><h5>$currentMonthDate</h5></div>";
      $rowDTContent .=      "<div><h5>$currentDayName</h5></div>";
      $rowDTContent .=      "<div><h5 class=\"smallBlue\">текущий день</h5></div>";
      $rowDTContent .=   "</div>";
      $rowDTContent .= "</td>";
    }

    $rowContent = "";

    for ($userNum = 0; $userNum < $userCount; $userNum++) {
      $userContext = get_report_user_context($usersInfo, $userNum);

      if ($userContext === null) {
        continue;
      }

      $rowContent .= get_cell_content_by_stat(
        $userContext['stats'],
        $index,
        $cellWidth,
        $userContext['id'],
        $userContext['default_start_time'],
        $userContext['allowed_delay']
      );
    }

    $rowsContent[] = $rowContent;
    $rowsDTContent[] = $rowDTContent;

    $headContent = "";

    for ($resType = 1; $resType <= 6; $resType++) {
      $rowResContent = "";
      $typeShowed = 0;

      for ($userNum = 0; $userNum < $userCount; $userNum++) {
        $userContext = get_report_user_context($usersInfo, $userNum);

        if ($userContext === null) {
          continue;
        }

        $rowResContent .= get_results_cell_content_by_stat(
          $userContext['stats'],
          $index,
          $cellWidth,
          $userContext['id'],
          $userContext['default_start_time'],
          $userContext['allowed_delay'],
          $resType,
          $typeShowed,
          $headContent
        );
      }

      if ($rowResContent != "") {
        $rowsContent[] = $rowResContent;
        $rowsDTContent[] = $headContent;
      }
    }

    $index++;
  }

  return array($rowsDTContent, $rowsContent);
}
