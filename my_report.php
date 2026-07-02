<?php
date_default_timezone_set("Asia/Novosibirsk");
ob_start();
session_start();
include_once __DIR__ . "/start.php";
if (
    !isset($_SESSION['rep_start_date']) ||
    !isset($_SESSION['rep_stop_date'])
) {
    $currDate = date('Y-m-d');

    list($start, $end) = getQuarterDates($currDate);

    $_SESSION['rep_start_date'] = $start;
    $_SESSION['rep_stop_date']  = $end;
    $_SESSION['rep_start_stop_date_mode'] = 4;
    $_SESSION['rep_start_stop_date_set'] = 1;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">                                                                                                                   
<head>
<title>Отчет посещаемости</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META NAME="Author" CONTENT="InTec">
<link rel="stylesheet" type="text/css" href="style/style.css" />
<link rel="stylesheet" type="text/css" href="style/main.css" />
</head>
<body onload="show_selectors()" bgcolor="#ffffff">

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 

<script type="text/javascript" charset="utf-8"> 

function set_period() {
  var reportType = document.getElementById('report_type').value;
  var manualBlock = document.getElementById('manual_rep');
  var startReportDate = document.getElementById('report_start_date').value;
  var stopReportDate = document.getElementById('report_stop_date').value;

  if (reportType == 7) {
    manualBlock.style.display = 'flex';
    return;
  }

  manualBlock.style.display = 'none';

  $.ajax({
    type: 'POST',
    url: 'ajax/set_report_date_interval.php',
    data: {
      report_type: reportType,
      start_report_date: startReportDate,
      stop_report_date: stopReportDate
    },
    cache: false,
    success: function(response) {
      if (typeof response === 'string' && response.indexOf('Ошибка') !== -1) {
        alert(response);
        return;
      }

      window.location.reload();
    },
    error: function(xhr) {
      var message = 'Не удалось изменить отчетный период.';

      if (xhr.responseText) {
        message += '\n\n' + xhr.responseText;
      }

      alert(message);
    }
  });
}

function manual_report_set() {
  var reportType = document.getElementById('report_type').value;
  var startReportDate = document.getElementById('report_start_date').value;
  var stopReportDate = document.getElementById('report_stop_date').value;

  if (!startReportDate || !stopReportDate) {
    alert('Укажите дату начала и дату окончания периода.');
    return;
  }

  if (startReportDate > stopReportDate) {
    alert('Дата начала периода не может быть позже даты окончания.');
    return;
  }

  $.ajax({
    type: 'POST',
    url: 'ajax/set_report_date_interval.php',
    data: {
      report_type: reportType,
      start_report_date: startReportDate,
      stop_report_date: stopReportDate
    },
    cache: false,
    success: function(response) {
      if (typeof response === 'string' && response.indexOf('Ошибка') !== -1) {
        alert(response);
        return;
      }

      if (typeof response === 'string' && response.indexOf('Слишком большой диапазон') !== -1) {
        alert(response);
        return;
      }

      window.location.reload();
    },
    error: function(xhr) {
      var message = 'Не удалось применить ручной отчетный период.';

      if (xhr.responseText) {
        message += '\n\n' + xhr.responseText;
      }

      alert(message);
    }
  });
}

function ta_delete( delID ){
  var perform=confirm('Запись будет удалена. Продолжить?')
  if ( perform == true ){
    $.post('ajax/time_delete.php', {delID: delID}, RetSWT);
    function RetSWT(dat) {
      window.location=self.location;
    }
  }
}

function close_add_time_list(){
  document.getElementById('adds_list_header').style.display='none';
}

function close_pause_time_list(){
  document.getElementById('pauses_list_header').style.display='none';
}

function close_penalties_list(){
  document.getElementById('penalty_list_header').style.display='none';
}

///////////////////////

function show_selectors(){
  var report_type = document.getElementById('report_type').value;

  if ( report_type == 7 ){
    document.getElementById('manual_rep').style.display='flex';
  }
  else{
    document.getElementById('manual_rep').style.display='none';
  } 
}
</script>

<?php
////////////////////////////////////////////////////////
include_once __DIR__ . "/funcs.php";
include_once __DIR__ . "/funcs_rep.php";

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
      include_once __DIR__ . "/navigate.php";
    }
    else{
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 0>";
    }
    echo "</td>";
                                    
    $wholeWidth = 1000;

    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";
    
///***

function getQuarterDates ($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }
  $month = (int)date('n', strtotime($date));
  $year = (int)date('Y', strtotime($date));

  if ($month >= 1 && $month <= 3) {
    $start = "$year-01-01";
    $end = "$year-03-31";
  } elseif ($month >= 4 && $month <= 6) {
    $start = "$year-04-01";
    $end = "$year-06-30";
  } elseif ($month >= 7 && $month <= 9) {
    $start = "$year-07-01";
    $end = "$year-09-30";
  } else {
    $start = "$year-10-01";
    $end = "$year-12-31";
  }

  $today = date("Y-m-d");
  
  if (strtotime($today) < strtotime($end)) {
    $end = $today;
  }

  return [$start, $end];
}

function getWeekDates($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }

  $dateValue = strtotime($date);
  $weekDayNumber = (int)date('N', $dateValue);

  $start = date('Y-m-d', strtotime('-' . ($weekDayNumber - 1) . ' days', $dateValue));
  $end = date('Y-m-d', $dateValue);

  return [$start, $end];
}

function getMonthDates($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }

  $start = date('Y-m-01', strtotime($date));
  $end = date('Y-m-d', strtotime($date));

  return [$start, $end];
}

function getPreviousMonthDates($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }

  $prevMonthDate = strtotime('first day of previous month', strtotime($date));

  $start = date('Y-m-01', $prevMonthDate);
  $end = date('Y-m-t', $prevMonthDate);

  return [$start, $end];
}

function getPreviousQuarterDates($date = null) {
  if ($date === null) {
    $date = date('Y-m-d');
  }

  list($currentQuarterStart) = getQuarterDates($date);
  $previousQuarterDate = date('Y-m-d', strtotime($currentQuarterStart . ' -1 day'));

  return getQuarterDates($previousQuarterDate);
}

function refreshReportPeriodDates($currDate) {
  if (!isset($_SESSION['rep_start_stop_date_mode'])) {
    $_SESSION['rep_start_stop_date_mode'] = 4;
  }

  if (!isset($_SESSION['rep_start_stop_date_set'])) {
    $_SESSION['rep_start_stop_date_set'] = 1;
  }

  $mode = (int)$_SESSION['rep_start_stop_date_mode'];

  if ($mode == 1) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getWeekDates($currDate);
  }
  else if ($mode == 2) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getMonthDates($currDate);
  }
  else if ($mode == 3) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getPreviousMonthDates($currDate);
  }
  else if ($mode == 4) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getQuarterDates($currDate);
  }
  else if ($mode == 5) {
    list($_SESSION['rep_start_date'], $_SESSION['rep_stop_date']) = getPreviousQuarterDates($currDate);
  }
}

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
        echo "<div id=\"manual_rep\" style=\"display:none;\">";

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

          echo "<input id=\"report_start_date\" align=\"center\" style=\"width:110px;\" type=\"date\" value=\"$manRepStart\" max=\"2060-12-31\">";
          echo " - <input id=\"report_stop_date\" align=\"center\" style=\"width:110px;\" type=\"date\" value=\"$manRepStop\" max=\"2060-12-31\">";
          echo "  <button class=\"button_style\" style=\"font-size: 90%; width:100px; height:21px; background-color:#f8d888; border:1px solid #888888; margin-left:3px\" onclick=\"manual_report_set();\" name=\"nextBtn\">Показать</button>";
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
  echo "<table class = \"no_padding\">";
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
            echo "<table>";
            //Заголовок
              echo "<tr>";
                for ( $userNum = 0; $userNum < $userCnt; $userNum ++ ){
                  $userFIO = $usersInfo[1][$userNum];
                  echo "<td class=\"report_no_padding\" bgcolor=\"#ffffff\" valign=\"middle\" align=\"center\" width = $cellWidth>";
                    echo "<div class=\"report_head_name\">";
                      echo "<h5>".$userFIO."</h5>";
                    echo "</div>";
                  echo "</td>";  
                }
                if ( $userCnt >= 9 || $userCnt == 1){
                  echo "<td class=\"report_no_padding\" bgcolor=\"#ffffff\" valign=\"middle\" align=\"center\">";
                    echo "<div class=\"report_head_stub\">";             
                    echo "</div>";
                  echo "</td>";
                }
            echo "</table>";
          echo "</div>";
        echo "</td>";
      echo "</tr>";
      echo "<tr>";
        echo "<td class=\"report_no_padding_no_border\">";
          echo "<div class=\"report_window_left\" id=\"report_window_left\">";
            echo "<table class = \"no_padding\">";
              //Левая панель
              for ( $idx = count( $rowsDTContent ); $idx >= 0; $idx -- ){
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
              echo "<table>";
              //Тело
                for ( $idx = count( $rowsContent ); $idx >= 0; $idx -- ){
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

include_once __DIR__ . "/end.php";

echo "</font>";
echo "</div>";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" charset="utf-8"> 

function update_clock(){
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat) {
    if ( document.getElementById('dateTimeFieldNav') ){
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId=setInterval( "update_clock()", 10000 );      

</script>
<script type="text/javascript" src="js/tory.js"></script>
</body>
</html> 
