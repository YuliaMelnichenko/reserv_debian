<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" src="js/tory.js?v=20260728-range"></script>
<script type="text/javascript" src="js/index-page.js?v=20260723"></script>

<?php
echo "<html lang=\"en\">";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body onload=\"check_day_change();\" bgcolor=\"#ffffff\" >";

include_once dirname(__DIR__) . "/php_tori/connect.php";

$currentDate = get_current_datetime_in_timezone_str( 1, 0 );
$user_dayTransitionTime = isset($_SESSION['ss_dayTransitionTime'])
  ? $_SESSION['ss_dayTransitionTime']
  : "06:00:00";

$timeArr = datetimestr_to_day_start_stop_DT_ex_str( $currentDate, $user_dayTransitionTime );

$startDTOuter = isset($timeArr[0]) ? $timeArr[0] : "";
$stopDTOuter = isset($timeArr[1]) ? $timeArr[1] : "";

echo "<div id=\"layer_div\" class=\"layer_div\">";
echo "</div>";

echo "<div id=\"layer_question_div\" class=\"layer_question_div_2 is-hidden\">";
echo "<table>";
  echo "<tr>";
    echo "<td class=\"report_small_padding\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 400px height = 120px>";
      echo "<table>";
        echo "<tr>";
          echo "<td class=\"report_no_padding_no_border_no_bg\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 400px height = 80px>";
            echo "<h5 class=\"big\">Произошла смена отчетного периода (суток).<br><br>Закрыть предыдущий период окончанием суток и начать новый период началом суток?<h5>";
          echo "</td>";
        echo "</tr>";
        echo "<tr>";
          echo "<td class=\"report_no_padding_no_border_no_bg\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 400px height = 40px>";

            echo "<table>";
              echo "<tr>";
                echo "<td class=\"report_no_padding_no_border_no_bg\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 198px>";
                  echo "<button class=\"day-change-button\" onclick=\"day_continue_confirm();\">Ok</button>";
                echo "</td>";
                echo "<td class=\"report_no_padding_no_border_no_bg\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 198px>";
                  echo "<button class=\"day-change-button\" onclick=\"day_continue_reject();\">Oтмена</button>";
                echo "</td>";
              echo "</tr>";
            echo "</table>";

          echo "</td>";
        echo "</tr>";
      echo "</table>";

    echo "</td>";
  echo "</tr>";
echo "</table>";

//
echo "</div>";

echo "<div id=\"pause_result_head\">";
echo "</div>";

echo "<div id=\"sport_pause\">";
echo "</div>";

echo "<div id=\"remote_work\">";
echo "</div>";

echo "<div id=\"pause_head\">";
echo "</div>";

echo "<div id=\"delay_explanation_head\">";
echo "</div>";

echo "<div id=\"delay_explanation_add_time_part\">";
echo "</div>";

echo "<div id=\"delay_out_time\">";
echo "</div>";

echo "<div id=\"delay_explanation_add_time\" >";
echo "</div>";

echo "<div id=\"delay_explanation_delay\">";
echo "</div>";

echo "<div align=\"left\">";

////////////////////////////////////////////////////////
if (
  isset($_SESSION['ss_id']) &&
  ($_SESSION['ss_id'] == 500 || $_SESSION['ss_id'] == 501)
) {
  header("Location: my_report.php");
  exit();
}

////////////////////////////////////////////////////////

  if ( isset( $_SESSION['ss_id'] ) )
  {
    $user_id = (int)$_SESSION['ss_id'];
    $user_rate = $_SESSION['ss_rate'];
    $user_defaultStartTime = $_SESSION['ss_defaultStartTime'];
    $user_defaultStartHour = $_SESSION['ss_defaultStartHour'];
    $user_defaultStartMinute = $_SESSION['ss_defaultStartMinute'];
    $user_allowedDelay = $_SESSION['ss_allowedDelay'];
    $user_timeZone = $_SESSION['ss_UserTimeZoneStr'];
    $user_defaultStartTimeWithDelay = $_SESSION['ss_defaultStartTimeWithDelay'];
    $user_RemoteWork = $_SESSION['ss_RemoteWork'];
    $user_RemoteWorkStr = $_SESSION['ss_RemoteWorkStr'];
    $user_dayTransitionTime = $_SESSION['ss_dayTransitionTime'];

    $currentDate = get_current_datetime_in_timezone_str( 1, 0 );

    $dateArr = datetimestr_to_day_start_stop_DT_ex_str_idx( $currentDate, $user_dayTransitionTime );

    $startDTStr = $dateArr[0];
    $stopDTStr = $dateArr[1];

    sync_time_registration_session_by_period($link, $user_id, $startDTStr, $stopDTStr);

    echo "<script type=\"text/javascript\">";
    echo "window.toriStopDTStr = " . json_encode($stopDTStr) . ";";
    echo "</script>";

    $_date = date('Y-m-d');
    $empty_dt = "0000-00-00 00:00:00";
    $bg_style = "";

    db_set_charset($link, "utf8");
    $query0 = db_query(
      $link,
      "SELECT state, surname, firstname, lastname FROM employees WHERE id = ?",
      'i',
      array($user_id)
    );
    $row0 = db_fetch_one($query0);
    $vn0 = db_num_rows($query0);

    $query = db_query($link, "SELECT eat_start_dt, eat_stop_dt FROM visiting WHERE user_id = ? AND DATE(in_dt) = CURDATE()", 'i', array($user_id));
    $row = db_fetch_one($query);

    $bg_style = "#ddeeff";

    echo "<table>";
    echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";

    include_once dirname(__DIR__) . "/navigate.php";

    echo "</td>";

    $wholeWidth = 625;

    echo "<td bgcolor=\"$bg_style\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

    echo "<h5 class=\"dark\"><br>/текущий день<br><br></h5>";

    if ( $vn0 == 1 )
    {
      $empl_state = $row0["state"];

      $sv_name = get_sv_name_by_userid( $user_id );

      db_set_charset($link, "utf8");

      $query01 = db_query(
        $link,
        "SELECT NAME, ROOM FROM departments WHERE ID IN (SELECT DEPID FROM GROUPS WHERE USERID = ?) LIMIT 1",
        'i',
        array($user_id)
      );

      $row01 = db_fetch_one($query01);

      $depName = $row01["NAME"];

      $room = $row01["ROOM"];

      echo "<table>";
      echo "<tr>";
      echo "<td bgcolor=\"$bg_style\" bordercolor=\"#888888\" valign=\"top\" align=\"left\">";

      $width00 = 600;
      $width11 = 320;
      $width22 = $width00 - $width11;
      $employeeAccountingErrorIcon = get_accounting_errors_count($link, (int)$user_id) > 0
        ? "<img class=\"accounting-error-attention\" src=\"img/attention.png\" title=\"Есть ошибки учета времени\" alt=\"Ошибки учета\">"
        : "";

      echo "<table>";
        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\" width = $width11>";
            echo "<span class=\"current-employee-info\">Сотрудник</span>";
          echo "</td>";
          echo "<td class=\"brd\" valign=\"top\" align=\"center\" width = $width22>";
            echo "<span class=\"current-employee-info\">" . html_escape($row0["surname"] . " " . $row0["firstname"] . " " . $row0["lastname"]) . $employeeAccountingErrorIcon . "</span>";
          echo "</td>";
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span class=\"current-employee-info\">Подразделение</span>";
          echo "</td>";
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span class=\"current-employee-info\">".$depName." (".$room." к.)"."</span>";
          echo "</td>";
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
          echo "<span class=\"current-employee-info\">Ответственный</span>";
          echo "</td>";
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span class=\"current-employee-info\">".$sv_name."</span>";
          echo "</td>";
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span class=\"current-employee-info\">Длительность рабочей недели</span>";
          echo "</td>";
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span class=\"current-employee-info\">".$user_rate." ч.</span>";
          echo "</td>";
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span class=\"current-employee-info\">Начало рабочего дня c допустимым опозданием</span>";
          echo "</td>";
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            if ( $user_RemoteWork == 1 )
            {
              echo "<span class=\"current-employee-info\">---</span>";
            }
            else
            {
              echo "<span class=\"current-employee-info\">".$user_defaultStartTime." >> ".$user_defaultStartTimeWithDelay." (+".$user_allowedDelay." мин.)</span>";
            }
          echo "</td>";
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span class=\"current-employee-info\">Часовой пояс</span>";
          echo "</td>";
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span class=\"current-employee-info\">".$user_timeZone."</span>";
          echo "</td>";
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span class=\"current-employee-info\">Текущий отчетный период</span>";
          echo "</td>";
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span class=\"current-employee-info\">".$startDTStr." - ".$stopDTStr."</span>";
          echo "</td>";
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span class=\"current-employee-info\">Режим работы</span>";
          echo "</td>";
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span class=\"current-employee-info\">".$user_RemoteWorkStr."</span>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";

      echo "<div id=\"delay_explanation_buildin\">";
      echo  "</div>";

      echo "<br><br>";

      echo "</td>";

      echo "</tr>";
      echo "</table>";

      echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">";

      if ( isset( $_SESSION['ss_state'] ) )
      {
      }
      else
      {
	      $_SESSION['ss_state'] = 1;
      }

    }

    echo "<div id=\"time_registration_div\">";
    echo "<h5 class=\"dark1\">Ожидание данных от сервера MySQL...</h5>";
    echo "</div>";

    echo "</td>";

    echo "<td bgcolor=\"#ffffff\" valign=\"top\" align=\"left\" width = 10>";
    echo "</td>";

    echo "<td bgcolor=\"$bg_style\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";
    echo "<h5 class=\"dark0\"><br>/присутствие сотрудников<br><br></h5>";
    echo "<div id=\"employee_activity\">";

    $employee_arr = index_fetch_presence_rows($link);

    $today = date('Y-m-d');
    $holidayDates = getHolidayDates($link, $today);
    $eventsToday = index_fetch_staff_events($link, $today);

    $vacationIcons = [
      3 => 'vacation3.png',
      2 => 'vacation2.png',
      1 => 'vacation1.png'
    ];

    $businessTripIcons = [
      3 => 'business_trip3.png',
      2 => 'business_trip2.png',
      1 => 'business_trip1.png'
    ];

    for ($i = 0; $i < count($employee_arr); $i++) {
      $zero_time = "0000-00-00 00:00:00";
      $name = $employee_arr[$i][0];
      $start = $employee_arr[$i][1];
      $stop = $employee_arr[$i][2];
      $img = $employee_arr[$i][3];
      $dat_in = $employee_arr[$i][4];
      $dat_out = $employee_arr[$i][5];
      $phone = $employee_arr[$i][6];
      $pers_phone = $employee_arr[$i][7];
      $corp_phone = $employee_arr[$i][8];
      $personal_id = $employee_arr[$i][9];
      $birth = $employee_arr[$i][10];
      $email = $employee_arr[$i][11];

      echo "<div class=\"activity\">";
      echo "<h5 class=\"activ_text\" data-phone-tooltip=\"u" . (int) $personal_id . "-contacts\">" . html_escape($name) . "</h5>";

      if ($dat_in == "") {
        echo "";
      }
      elseif ($dat_in != $zero_time && $dat_out === $zero_time) {
        echo "<h5 class=\"activ_time\">$start</h5>";
      }
      else {
        echo "<h5 class=\"activ_time\">".$start." - ".$stop."</h5>";
      }

      echo "<div class=\"img_container\">";

      if (!in_array($personal_id, [400, 500, 501])) {
        if (!empty($birth) && $birth == date('m-d')) {
          echo "<img class=\"presence-inline-icon\" title=\"C днем рождения!\" src=\"img/birthday.png\">";
        }
      }

      if (isset($eventsToday[$personal_id])){
        foreach ($eventsToday[$personal_id] as $event) {
          $start = $event['start_date'];
          $stop = $event['stop_date'];
          $today = date('Y-m-d');

          $daysLeft = getWorkingDaysUntil($today, $start, $holidayDates);

          if ($event['event'] === 'Отпуск' && $today <= $stop) {
            $tooltipDate = "Отпуск: " . date("d.m.Y", strtotime($start)) . " - " . date("d.m.Y", strtotime($stop));

            if ($today >= $start && $today <= $stop) {
              $daysToEnd = getDaysLeft($stop, $today);
              $tooltip = "До конца отпуска осталось: $daysToEnd " . getDayWord($daysToEnd) . "\nОтпуск: " . date("d.m.Y", strtotime($start)) . " - " . date("d.m.Y", strtotime($stop));
              echo "<img class=\"employee-event-icon\" src=\"img/vacation.png\" title=\"$tooltip\">";
            } elseif ($today < $start) {
                $daysLeft = getWorkingDaysUntil($today, $start, $holidayDates);

                if (array_key_exists($daysLeft, $vacationIcons)) {
                  $tooltip = "Осталось $daysLeft " . "рабочих " . getDayWord($daysLeft) . " до отпуска. \n$tooltipDate";
                  $icon = $vacationIcons[$daysLeft];
                  echo "<img class=\"employee-event-icon\" src=\"img/$icon\" title=\"$tooltip\">";
                }
              } else {
                "<!-- ni icon for $daysLeft days -->";
              }
          }

          if ($event['event'] === 'Командировка' && $today <= $stop) {
            $tooltipDate = "Командировка: " . date("d.m.Y", strtotime($start)) . " - " . date("d.m.Y", strtotime($stop));

            if ($today >= $start && $today <= $stop) {
              $daysToEnd = getDaysLeft($stop, $today);
              $tooltip = "До конца командировки осталось: $daysToEnd" . getDayWord($daysToEnd) . "\nКомандировка: " . date("d.m.Y", strtotime($start)) . " - " . date("d.m.Y", strtotime($stop));
              echo "<img class=\"employee-event-icon\" src=\"img/business_trip.png\" title=\"$tooltip\">";
            } elseif ($today < $start) {
              $daysLeft = getWorkingDaysUntil($today, $start, $holidayDates);

              if (array_key_exists($daysLeft, $businessTripIcons)) {
                $tooltip = "Осталось $daysLeft " . "рабочих " . getDayWord($daysLeft) . " до командировки. \n$tooltipDate";
                $icon = $businessTripIcons[$daysLeft];
                echo "<img class=\"employee-event-icon\" src=\"img/$icon\" title=\"$tooltip\">";
              }
            }
          }

          if ($event['event'] === 'Больничный') {
            if ($today >= $start && $today <= $stop) {
              $daysToEnd = getDaysLeft($stop, $today);
              $tooltip = "До конца больничного осталось: $daysToEnd " . getDayWord($daysToEnd) . ". \nБольничный: " . date("d.m.Y", strtotime($start)) . " - " . date("d.m.Y", strtotime($stop));
              echo "<img class=\"employee-event-icon\" src=\"img/sick.png\" title=\"$tooltip\">";
            }
          }
        }
      }

      echo $img;

      echo "</div>";
      echo "</div>";
      get_phone_info($personal_id, $phone, $pers_phone, $corp_phone, $email);

    }
    echo "</div>";
    echo "</td>";

    echo "<td bgcolor=\"#ffffff\" valign=\"top\" align=\"left\" width = 10>";
    echo "</td>";

    echo "<td class=\"plain-cell\" valign=\"top\" align=\"left\">";
    echo "<div id=\"inform\">";
    echo "<h5 class=\"dark0\"><br>/обновления кнопки 16.08.2024г.:<br><br></h5>";
    echo "<h5 class=\"dark1\">1. Настроено отображение присутствия сотрудников на рабочем месте информационной плиткой \"/присутствие сотрудников \" на главной вкладке.<br></h5>";
    echo "<h5 class=\"dark1\">2. Добавлена кнопка регистрации ухода в тренажерный зал. (функционал приостановки времени).<br></h5>";
    echo "<h5 class=\"dark1\">3. Добавлена кнопка \"Тренажерный зал\" в панели навигации. В данной вкладке отображается список сотрудников, присутствующих в данный момент в спортивном зале.<br></h5>";
    echo "<h5 class=\"dark1\">4. Добавлена возможность записаться в спортзал во вкладке \"Тренажерный зал\".<br></h5>";
    echo "<h5 class=\"dark1\">5. Скрыт информационный блок с обновлениями.<br></h5>";
    echo "</div>";
    echo "</td>";

    echo "<td bgcolor=\"#ffffff\" valign=\"top\" align=\"left\" width = 10>";
    echo "</td>";

    echo "<td class=\"plain-cell\" valign=\"top\" align=\"left\">";
    echo "<div id=\"birthday_block\">";
    echo "</div>";
    echo "</td>";

    if ( $_SESSION['ss_id'] == 3000 )
    {

      $dateMonth = date('Y-m-d');

      $dateMonth = set_to_first_month_day( $dateMonth );

      if ( ! isset( $_SESSION['stat_month_count'] ) )
      {
        $_SESSION['stat_month_count'] = 2;
      }

      $monthCnt = $_SESSION['stat_month_count'];

      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 550>";
        echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
          echo "<tr>";
            echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"center\" align=\"left\" width = 510 height = 16>";
                echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">Краткая статистика за текущий и предыдущие месяцы ($monthCnt) ";
		echo "<img src=\"img/plus.bmp\" onclick=\"st_month_inc();\" />";
		echo "<img src=\"img/minus.bmp\" onclick=\"st_month_dec();\" />";
		echo "<img src=\"img/dva.bmp\" onclick=\"st_month_def();\" />";
                echo "</font>";
	    echo "</td>";
	  echo "</tr>";

          $monthNumBase = date('m');

	  for ( $monthNum = 0; $monthNum  < $_SESSION['stat_month_count']; $monthNum ++ )
          {
	    echo "<tr align=\"left\">";
              echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" width = 40>";

                $dateMonth = MonthDecDN( $dateMonth, $monthNum );

                show_month_stat( $dateMonth, $user_id, $user_rate, $user_defaultStartTime, $user_defaultStartHour, $user_defaultStartMinute, $user_allowedDelay );
              echo "</td>";
            echo "</tr>";
          }
        echo "</table>";
      echo "</td>";
    }

    echo "</tr>";

    echo "</table>";

  }
  echo "<font size=\"2\" color=\"#444444\" face=\"Arial\">";
    include_once dirname(__DIR__) . "/end.php";
  echo "</font>";
echo "</div>";

?>


<?php
echo "</body>";
echo "</html>";
?>
