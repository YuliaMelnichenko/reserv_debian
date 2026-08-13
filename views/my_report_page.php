<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
<title>Отчет посещаемости</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META NAME="Author" CONTENT="InTec">
<link rel="stylesheet" type="text/css" href="style/main.css" />
</head>
<body onload="show_selectors()" bgcolor="#ffffff">

<script type="text/javascript" src="lib/jquery/jquery.js"></script>

<script type="text/javascript" src="js/my-report.js?v=20260723"></script>

<?php
////////////////////////////////////////////////////////
include_once dirname(__DIR__) . "/funcs.php";
include_once dirname(__DIR__) . "/funcs_rep.php";

save_last_location( "my_report.php" );

auth();
////////////////////////////////////////////////////////
$dateArr = get_current_datetime_in_timezone();
$currDate = $dateArr[2];
////////////////////////////////////////////////////////

echo "<div id=\"adds_list_header\">";
echo "</div>";

echo "<div id=\"pauses_list_header\">";
echo "</div>";

echo "<div id=\"penalty_list_header\">";
echo "</div>";

echo "<div align=\"left\">";
  echo "<table>";
    echo "<tr>";
    $ss_id_tmp = $_SESSION['ss_id'];

    $directorView = 0;
    if ( $ss_id_tmp == 5000 ){
      $directorView = 1;
    }

    if ( $directorView == 0 ){
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";
      include_once dirname(__DIR__) . "/navigate.php";
    }
    else{
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 0>";
    }
    echo "</td>";

    echo "<td class=\"report-page-content\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\">";

///***


if (!isset($_SESSION['rep_start_stop_date_mode'])) {
  $_SESSION['rep_start_stop_date_mode'] = 4;
}

if (!isset($_SESSION['rep_start_stop_date_set'])) {
  $_SESSION['rep_start_stop_date_set'] = 1;
}

refreshReportPeriodDates($currDate);

if (
  !isset($_SESSION['report_cache_date']) ||
  $_SESSION['report_cache_date'] != $currDate ||
  !isset($_SESSION['report_cache_mode']) ||
  $_SESSION['report_cache_mode'] != $_SESSION['rep_start_stop_date_mode'] ||
  !isset($_SESSION['report_cache_start_date']) ||
  $_SESSION['report_cache_start_date'] != $_SESSION['rep_start_date'] ||
  !isset($_SESSION['report_cache_stop_date']) ||
  $_SESSION['report_cache_stop_date'] != $_SESSION['rep_stop_date']
) {
  unset($_SESSION['full_report']);
  unset($_SESSION['usersInfo']);
  unset($_SESSION['report_stats']);
  unset($_SESSION['rowsContents']);

  $_SESSION['report_cache_date'] = $currDate;
  $_SESSION['report_cache_mode'] = $_SESSION['rep_start_stop_date_mode'];
  $_SESSION['report_cache_start_date'] = $_SESSION['rep_start_date'];
  $_SESSION['report_cache_stop_date'] = $_SESSION['rep_stop_date'];
}

$rep_start_stop_date_mode = $_SESSION['rep_start_stop_date_mode'];
$selected = $rep_start_stop_date_mode - 1;

$selectedArr = array_fill(0, 7, "");
$selectedArr[$selected] = "selected";

echo "<div id=\"report_container\">";
  echo "<div>";
    echo "<h5 class=\"dark\">ОТЧЕТ ПОСЕЩАЕМОСТИ</h5>";
  echo "</div>";
  echo "<div>";
      echo "<h4 class=\"small\">Отчетный период: </h4>";
    echo "</div>";
    echo "<div>";
      echo "<select onchange=\"set_period();\" class=\"flat\" id=\"report_type\" bgcolor=\"#888888\" width = 70 >";
        echo "<option value=\"1\" $selectedArr[0]>С начала недели</option>";
        echo "<option value=\"2\" $selectedArr[1]>С начала месяца</option>";
        echo "<option value=\"3\" $selectedArr[2]>С начала предыдущего месяца</option>";
        echo "<option value=\"4\" $selectedArr[3]>С начала квартала</option>";
        echo "<option value=\"5\" $selectedArr[4]>Предыдущий квартал</option>";
    //  echo "<option value=\"6\" $selectedArr[5]>С начала года</option>";
        echo "<option value=\"7\" $selectedArr[6]>Задать вручную</option>";
      echo "</select>";
    echo "</div>";
    echo "<div id=\"select_reporting_period\">";
      echo "<h4 class=\"small\">Выбранный отчетный период: ".$_SESSION['rep_start_date']." - ".$_SESSION['rep_stop_date'] ."</h4>";
        echo "<div id=\"manual_rep\" class=\"is-hidden\">";

          if ( isset( $_SESSION['rep_start_date'] ) ){
            $manRepStart = $_SESSION['rep_start_date'];
          }
          else {
            $manRepStart = $currDate;
          }
          if ( isset( $_SESSION['rep_stop_date'] ) ){
            $manRepStop = $_SESSION['rep_stop_date'];
          }
          else {
            $manRepStop = $currDate;
          }

          echo "<input id=\"report_start_date\" class=\"report-date-input\" align=\"center\" type=\"date\" value=\"" . html_escape($manRepStart) . "\" max=\"2060-12-31\">";
          echo " - <input id=\"report_stop_date\" class=\"report-date-input\" align=\"center\" type=\"date\" value=\"" . html_escape($manRepStop) . "\" max=\"2060-12-31\">";
          echo "  <button class=\"button_style report-show-button\" onclick=\"manual_report_set();\" name=\"nextBtn\">Показать</button>";
        echo "</div>";
    echo "</div>";
echo "</div>";

$svID = $_SESSION['ss_id'];

$user_defaultStartTimeStr = $_SESSION['ss_defaultStartTime'];
$user_allowedDelay = $_SESSION['ss_allowedDelay'];

$autoTodayModes = array(1, 2, 4, 6);

if (
  in_array((int)$_SESSION['rep_start_stop_date_mode'], $autoTodayModes)
  && $_SESSION['rep_stop_date'] < $currDate
) {
  $_SESSION['rep_stop_date'] = $currDate;

  unset($_SESSION['full_report']);
  unset($_SESSION['usersInfo']);
  unset($_SESSION['report_stats']);
  unset($_SESSION['rowsContents']);
}

$rep_start_date = $_SESSION['rep_start_date'];
$rep_stop_date = $_SESSION['rep_stop_date'];

$userIDs = array();

if ( !isset( $_SESSION['full_report'] ) ){
  $usersInfo = get_group_user_info_by_svID_for_report_ex( $svID );
}

$userCnt = count($usersInfo[0]);

for ( $userNum = 0; $userNum < $userCnt; $userNum ++ ){
  $userID = $usersInfo[0][$userNum];
  $userRate = $usersInfo[2][$userNum];

   $stats = get_stat_set_by_range_full_ex( $rep_start_date, $rep_stop_date, $userID, $userRate );

  $usersInfo[7][$userNum] = $stats;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////
$rowsContents = get_report_body_row_contents( $usersInfo );

$rowsDTContent = $rowsContents[0];
$rowsContent = $rowsContents[1];

///////////////////////////////////////////////////////////////////////////////////////////////////////////////

$dateWidth = 205;
$cellWidth = 165;
$layersWidth = $dateWidth + $cellWidth*$userCnt + $userCnt*20;
$layersWidth = 500;

echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
  echo "<tr height = 10>";
    echo "<td>";
    echo "</td>";
  echo "</tr>";
echo "</table>";

echo "<div class=\"report_window_main\" id=\"report_window_main\">";
  echo "<table class=\"no_padding report-layout-table\">";
    echo "<tr>";
      echo "<td class=\"report_no_padding_no_border\">";
        echo "<div class=\"report_window_head_left\" id=\"report_window_head_left\">";
          echo "<img src=\"/img/report_head_left.png\">";
        echo "</div>";
      echo "</td>";

      echo "<td class=\"report_no_padding_no_border\">";
        if ( $userCnt == 1 ){
          echo "<div class=\"report_window_head_single\" id=\"report_window_head_single\">";
        }
        else{
          echo "<div class=\"report_window_head\" id=\"report_window_head\">";
        }
            echo "<table class=\"report-window-header-table\">";
            //Заголовок
              echo "<tr>";
                for ( $userNum = 0; $userNum < $userCnt; $userNum ++ ){
                  $userFIO = $usersInfo[1][$userNum];
                  $userNameParts = isset($usersInfo[8][$userNum])
                    ? $usersInfo[8][$userNum]
                    : array(
                      "surname" => $userFIO,
                      "firstname" => "",
                      "lastname" => "",
                    );
                  echo "<td class=\"report_no_padding report-header-user-cell\" bgcolor=\"#ffffff\" valign=\"middle\" align=\"center\" width = $cellWidth>";
                    echo "<div class=\"report_head_name report-header-user-name\">";
                      echo "<div class=\"report_head_fio\">";
                        echo "<div class=\"report_head_surname\">" . html_escape($userNameParts["surname"]) . "</div>";
                        echo "<div class=\"report_head_patronymic\">";
                          echo html_escape($userNameParts["firstname"]);
                          if ($userNameParts["lastname"] !== "") {
                            echo "<br>" . html_escape($userNameParts["lastname"]);
                          }
                        echo "</div>";
                      echo "</div>";
                    echo "</div>";
                  echo "</td>";
                }
                if ( $userCnt >= 9 || $userCnt == 1){
                  echo "<td class=\"report_no_padding\" bgcolor=\"#ffffff\" valign=\"middle\" align=\"center\">";
                    echo "<div class=\"report_head_stub\">";
                    echo "</div>";
                  echo "</td>";
                }
              echo "</tr>";
            echo "</table>";
          echo "</div>";
        echo "</td>";
      echo "</tr>";
      echo "<tr>";
        echo "<td class=\"report_no_padding_no_border\">";
          echo "<div class=\"report_window_left\" id=\"report_window_left\">";
            echo "<table class = \"no_padding\">";
              //Левая панель
              for ( $idx = count( $rowsDTContent ) - 1; $idx >= 0; $idx -- ){
                echo "<tr>";
                  echo $rowsDTContent[$idx];
                echo "</tr>";
              }
              echo "<tr>";
                echo "<td class=\"report_no_padding_no_border\" valign=\"middle\" align=\"center\">";
                  echo "<div class=\"report_head_stub_left\">";
                  echo "</div>";
                echo "</td>";
              echo "</tr>";
            echo "</table>";
          echo "</div>";
        echo "</td>";

        echo "<td class=\"report_no_padding_no_border\">";
          if ( $userCnt == 1 ){
            echo "<div class=\"report_window_single\" id=\"report_window_single\" onscroll=\"make_div_scroll_single();\">";
          }
          else{
            echo "<div class=\"report_window\" id=\"report_window\" onscroll=\"make_div_scroll();\">";
          }
              echo "<table class=\"report-window-content-table\">";
              //Тело
                for ( $idx = count( $rowsContent ) - 1; $idx >= 0; $idx -- ){
                  echo "<tr>";
                    echo $rowsContent[$idx];
                  echo "</tr>";
                }

              echo "</table>";
            echo "</div>";
          echo "</td>";

        echo "</tr>";
      echo "</table>";

    echo "</div>";

  echo "</td>";
echo "</tr>";
echo "</table>";

echo "<font size=\"2\" color=\"#444444\" face=\"Arial\">";

include_once dirname(__DIR__) . "/end.php";

echo "</font>";
echo "</div>";
?>

<script type="text/javascript" src="js/tory.js?v=20260730-report-layout"></script>
</body>
</html>
