<?php

date_default_timezone_set("Asia/Novosibirsk");
ob_start();
require_once __DIR__ . '/inc/session.php';
include_once __DIR__ . "/start.php";
include_once __DIR__ . "/funcs.php";
auth();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" src="js/tory.js?v=20260709"></script>
<script type="text/javascript" charset="utf-8"> 

var timerIdSessValid = setInterval(check_sess, 500000);

function check_sess(){
  $.ajax({
    url: 'ajax/sync_current_period.php',
    method: 'POST',
    dataType: 'json',
    success: function(dat) {
      if (!dat || dat.valid != 1) {
        window.location = self.location;
        return;
      }

      if (dat.refreshTimeRegistration == 1) {
        if (dat.stopDTStr) {
          window.toriStopDTStr = dat.stopDTStr;
        }

        get_time_registration_div_content();
        build_in_delay_expl();

        if (typeof update_clock === 'function') {
          update_clock();
        }
      }
    },
    error: function() {
      window.location = self.location;
    }
  });
}

function hide_day_change_layers(){
  var layerDiv = document.getElementById('layer_div');
  var layerQuestionDiv = document.getElementById('layer_question_div');

  if ( layerDiv ){
    layerDiv.style.display='none';
  }

  if ( layerQuestionDiv ){
    layerQuestionDiv.style.display='none';
  }
}

function check_day_change(){
  hide_day_change_layers();

  $.post('ajax/check_day_change.php', RetSWT);
  function RetSWT(dat){
    if ( dat == 1 ){
      clearInterval( timerIdDayChange );
      hide_day_change_layers();
    }
  }
}

var timerIdDayChange = setInterval(check_day_change, 3000);

function day_continue_confirm()
{
  $.post('ajax/day_continue_confirm.php', RetSWT);
  function RetSWT(dat) 
  {
    hide_day_change_layers();

    window.location=self.location;
  }   
}

function day_continue_reject()
{
  $.post('ajax/day_continue_reject.php', RetSWT);
  function RetSWT(dat) 
  {
    hide_day_change_layers();

    window.location=self.location;
  }   
}

$(document).ready(function() 
{
  var hidden, visibilityState, visibilityChange;

  if (typeof document.hidden !== "undefined") 
  {
    hidden = "hidden", visibilityChange = "visibilitychange", visibilityState = "visibilityState";
  } 
  else if (typeof document.msHidden !== "undefined") 
  {
    hidden = "msHidden", visibilityChange = "msvisibilitychange", visibilityState = "msVisibilityState";
  }

  var document_hidden = document[hidden];

  document.addEventListener(visibilityChange, function() 
  {
    if(document_hidden != document[hidden]) 
    {
      if(document[hidden]) 
      {
        //alert('hidden');
      } 
      else 
      {
        check_sess();
      }

      document_hidden = document[hidden];
    }
  });
});

check_pause_state();

function st_month_inc()
{	
  $.post('ajax/stat_month_inc.php', RetSWT);
  function RetSWT(dat) 
    {  
      window.location=self.location;
    }
}

function st_month_dec()
{	
  $.post('ajax/stat_month_dec.php', RetSWT);
  function RetSWT(dat) 
  {  
    window.location=self.location;
  }
}

function st_month_def()
{	
  $.post('ajax/stat_month_def.php', RetSWT);
  function RetSWT(dat) 
  {  
    window.location=self.location;
  }
}

function get_time_registration_div_content()
{
  $.post('ajax/get_time_registration_div.php', RetSWT6 );
  function RetSWT6(dat6)
  {    
    if ( document.getElementById('time_registration_div') ){ document.getElementById('time_registration_div').innerHTML = dat6; }
  }
} 

// function switch_day_state( next ) {
//   $.post('ajax/switch_day_state.php', { next: next }, RetSWT);
//   function RetSWT(dat) { 
//     if ( dat == 1 ){
//       get_time_registration_div_content();
//     } else {
//       alert( dat );
//     }
//   }
//   build_in_delay_expl();
// }

function switch_day_state(next, callback) {
  $.post('ajax/switch_day_state.php', {next: next}, function(dat) {
    if (dat.trim() === "1") {
      if (typeof callback === 'function') callback();
      else get_time_registration_div_content();
    } else {
      alert(dat);
    }
  });
  build_in_delay_expl();
}

function rollback_state()
{
  var perform=confirm('будет осуществлен возврат к предыдущему состоянию регистрации времени. Продолжить?')
  if ( perform == true )
  {
    switch_day_state( 0 );
  }
}

function reg_in_work_with_delay()
{
  reg_in_work();

  set_delay();
}

function reg_in_work()
{
  switch_day_state( 1 );
}

function reg_out_work()
{
  switch_day_state( 1 );
}

function reg_eat_start() {
  switch_day_state(1, function() {
    $.get('ajax/set_lunch.php', function(html) {
      $('#lunchPauseFullScreen').remove();

      if ($.trim(html) !== '') {
        $('body').append(html);
      }
      else {
        get_time_registration_div_content();
      }
    });
  });
}

function reg_eat_stop() {
  switch_day_state(1, function() {
    $('#lunchPauseFullScreen').remove();
    location.reload();
  });
}

$(document).ready(function() {
  $.get('ajax/get_current_state.php', function(state) {
    if (parseInt(state, 10) === 3) {
      $.get('ajax/set_lunch.php', function(html) {
        $('#lunchPauseFullScreen').remove();

        if ($.trim(html) !== '') {
          $('body').append(html);
        }
      });
    }
    else {
      $('#lunchPauseFullScreen').remove();
    }
  });
});

function add_expl()
{
  $.post('ajax/get_add_time_notif_count.php', RetSWT2);
  function RetSWT2(dat2) 
  { 
    if ( document.getElementById('notifBtn') )
    { 
      document.getElementById('notifBtn').innerHTML = dat2; 
    }
  }
  $.post('ajax/get_delay_notif_count.php', RetSWT2);
  function RetSWT2(dat2) 
  { 
    if ( document.getElementById('notifDelayBtn') )
    { 
      document.getElementById('notifDelayBtn').innerHTML = dat2; 
    }
  }
    
  $.post('ajax/get_explanation_head.php', RetSWT1);
  function RetSWT1(dat1) 
  {
    if ( document.getElementById('delay_explanation_head') )
    {
      document.getElementById('delay_explanation_head').innerHTML=dat1;
      document.getElementById('delay_explanation_head').style.display='block';
    }
  }
}

function add_training_time()
{
  $.post('ajax/get_add_gym_time.php', RetSWT1);
  function RetSWT1(dat1) 
  { 
    if ( document.getElementById('delay_explanation_sport_time') )
    {
      document.getElementById('delay_explanation_sport_time').innerHTML = dat1;
      document.getElementById('delay_explanation_sport_time').style.display='block';
    }
  }
}

function close_add_sport_time()
{
  if ( document.getElementById('delay_explanation_sport_time') ){ document.getElementById('delay_explanation_sport_time').style.display='none'; }
}

function enter_out_time(){
  $.post('ajax/get_out_time.php', RetSWT1);
  function RetSWT1(dat1) {
    if ( document.getElementById('delay_out_time') ){
      document.getElementById('delay_out_time').innerHTML=dat1;
      document.getElementById('delay_out_time').style.display='flex';
    }
  }
}

function enter_stop_eat_time()
{
  $.post('ajax/get_eat_stop.php', RetSWT1);
  function RetSWT1(dat1) 
  {
    if ( document.getElementById('delay_out_time') )
    {
      document.getElementById('delay_out_time').innerHTML=dat1;
      document.getElementById('delay_out_time').style.display='flex';
    }
  }
}

function close_out_time()
{
  if ( document.getElementById('delay_out_time') ){ document.getElementById('delay_out_time').style.display='none'; }
}

function as_add_time()
{
  $.post('ajax/get_add_times.php', RetSWT1);
  function RetSWT1(dat1) 
  { 
    if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
    if ( document.getElementById('delay_explanation_add_time') )
    {
      document.getElementById('delay_explanation_add_time').innerHTML = dat1;
      document.getElementById('delay_explanation_add_time').style.display='block';
    }

    $.post('ajax/get_add_time_notif_count.php', RetSWT2);
    function RetSWT2(dat2) 
    { 
      if ( document.getElementById('notifBtn') )
      { 
        document.getElementById('notifBtn').innerHTML = dat2; 
      }
    }
    $.post('ajax/get_delay_notif_count.php', RetSWT2);
    function RetSWT2(dat2) 
    { 
      if ( document.getElementById('notifDelayBtn') )
      { 
        document.getElementById('notifDelayBtn').innerHTML = dat2; 
      }
    }
  }
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
  if ( document.getElementById('delay_explanation_add_time') ){ document.getElementById('delay_explanation_add_time').style.display='block'; }
}

function close_explanation_head()
{
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
}

function build_in_delay_expl()
{
  $.post('ajax/get_delay_explanation_build_in.php', RetSWT2 );
  function RetSWT2(dat2)
  {
    if ( document.getElementById('delay_explanation_buildin') ){ document.getElementById('delay_explanation_buildin').innerHTML = dat2; }
    if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display = 'none'; }
  }
}
function build_in_add_work()
{
/*  $.post('ajax/get_add_time_build_in.php', RetSWT2 );
  function RetSWT2(dat2)
  { 
    if ( document.getElementById('add_work_buildin') ){ document.getElementById('add_work_buildin').innerHTML = dat2; }
  }*/ 
}

function as_delay()
{
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
  if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display='block'; }

  $.post('ajax/get_delay_explanation.php', {}, RetSWT2 );
  function RetSWT2(dat2)
  {
    if ( document.getElementById('delay_explanation_delay') )
    { 
      document.getElementById('delay_explanation_delay').innerHTML = dat2; 
      if ( document.getElementById('explAddInfo') )
      {
        var blockHeight = document.getElementById('delay_explanation_delay').offsetHeight - 15;
        var addHeight = document.getElementById('explAddInfo').offsetHeight;
   
        document.getElementById('delay_explanation_delay').style.height = blockHeight + addHeight  + "px";
      }
    }
  }
}

</script>

<?php
echo "<html lang=\"en\">";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body class=\"app-page\" onload=\"check_day_change();\">";

include_once __DIR__ . "/php_tori/connect.php";

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

echo "<div class=\"current-day-layout\">";

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

    mysqli_set_charset($link, "utf8");
    $query0 = db_query($link, "SELECT * FROM employees WHERE id = ?", 'i', array($user_id));
    $row0 = mysqli_fetch_assoc($query0); 
    $vn0=mysqli_num_rows($query0);

    $query = db_query($link, "SELECT eat_start_dt, eat_stop_dt FROM visiting WHERE user_id = ? AND DATE(in_dt) = CURDATE()", 'i', array($user_id));
    $row = mysqli_fetch_assoc($query);

    $bg_style = "#ddeeff";

    echo "<table>";
    echo "<tr>";
    echo "<td class=\"current-day-nav-cell\">";

    include_once __DIR__ . "/navigate.php";

    echo "</td>";               

    $wholeWidth = 625;

    echo "<td class=\"current-day-content-cell\">";

    echo "<h5 class=\"dark\"><br>/текущий день<br><br></h5>";
        
    if ( $vn0 == 1 )
    {
      $empl_state = $row0["state"];
            
      $sv_name = get_sv_name_by_userid( $user_id );

      mysqli_set_charset($link, "utf8");
    
      $query01 = db_query($link, "SELECT * FROM departments WHERE ID IN (SELECT DEPID FROM GROUPS WHERE USERID = ?) LIMIT 1", 'i', array($user_id));

      $row01 = mysqli_fetch_assoc($query01);

      $depName = $row01["NAME"];

      $room = $row01["ROOM"];     

      echo "<table>";
      echo "<tr>";
      echo "<td class=\"current-day-profile-cell\">";

      $width00 = 600;  
      $width11 = 320; 
      $width22 = $width00 - $width11;
      $employeeAccountingErrorIcon = get_accounting_errors_count($link, (int)$user_id) > 0
        ? "<img class=\"accounting-error-attention\" src=\"img/attention.png\" title=\"Есть ошибки учета времени\" alt=\"Ошибки учета\">"
        : "";

      echo "<table>";
        echo "<tr>";
          echo "<td class=\"brd current-day-profile-label\">";
            echo "<span class=\"current-employee-info\">Сотрудник</span>";
          echo "</td>";  
          echo "<td class=\"brd current-day-profile-value\">";
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

      echo "<div class=\"current-day-main-content\">";

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

    mysqli_set_charset($link, "utf8");
    $bosses_arr = [];

    $bosses_sql = "SELECT id, firstname, surname, lastname, DATE_FORMAT(birthday, '%m-%d') AS bday FROM employees WHERE id IN (400, 500, 501)";

    $bosses_q = db_query($link, $bosses_sql);

    if ($bosses_q) {
      while ($b = mysqli_fetch_assoc($bosses_q)) {
        if (isset($b['bday']) && $b['bday'] === date('m-d')) {
          $full_name = trim($b['surname'] . " " . $b['firstname'] . " " . $b['lastname']);
          $img = "<img class=\"presence-inline-icon\" title=\"C днем рождения!\" src=\"img/birthday.png\">";
          $bosses_arr[] = [
            $full_name,
            "",
            "",
            $img,
            "",
            "",
            "",
            "",
            "",
            $b['id'],
            $b['bday'],
            ""
          ];
        }
      }
    }

    $query6 = db_query($link, "SELECT id, firstname, surname, lastname, phone, personal_phone, corporate_phone, DATE_FORMAT(birthday, '%m-%d'), email FROM employees WHERE relevance = 1 AND id NOT IN (400, 500, 501) ORDER BY surname");

    echo "<td bgcolor=\"$bg_style\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";
    echo "<h5 class=\"dark0\"><br>/присутствие сотрудников<br><br></h5>";
    echo "<div id=\"employee_activity\">";

    $employee_arr = array();

    while ($row6 = mysqli_fetch_assoc($query6)) {
      $id_empl = $row6["id"];
      $surname = $row6["surname"];
      $firstname = $row6["firstname"];
      $lastname = $row6["lastname"];
      $phone_number = $row6["phone"];
      $personal_phone = $row6["personal_phone"];
      $corporate_phone = $row6["corporate_phone"];
      $birthday = $row6["DATE_FORMAT(birthday, '%m-%d')"];
      $full_name = $surname." ".$firstname." ".$lastname;
      $email_empl = $row6["email"];

      $time = "0000-00-00 00:00:00";

      mysqli_set_charset($link, "utf8");
      $query5 = db_query(
        $link,
        'SELECT in_dt, eat_start_dt, eat_stop_dt, out_dt FROM visiting WHERE DATE(in_dt) = CURDATE() AND user_id = ? ORDER BY in_dt DESC, ID DESC LIMIT 1',
        'i',
        array($id_empl)
      );

      if (!$query5) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        exit;
      }

      $row5 = mysqli_fetch_assoc($query5);

      $query7 = db_query(
        $link,
        "SELECT START_DT, STOP_DT FROM ADD_TIME WHERE DATE(START_DT) = CURDATE() AND USERID = ? AND (STOP_DT IS NULL OR STOP_DT = '0000-00-00 00:00:00') ORDER BY START_DT DESC, ID DESC LIMIT 1",
        'i',
        array($id_empl)
      );

      if (!$query7) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        exit;
      }

      $row7 = mysqli_fetch_assoc($query7);

      $remote_sql = "SELECT id, start_dt, stop_dt FROM remote_work WHERE user_id = ? AND DATE(start_dt) = CURDATE() ORDER BY id DESC LIMIT 1";

      $remote_stmt = mysqli_prepare($link, $remote_sql);
      mysqli_stmt_bind_param($remote_stmt,  "i", $id_empl);
      mysqli_stmt_execute($remote_stmt);
      $remote_res = mysqli_stmt_get_result($remote_stmt);
      $remote_row = mysqli_fetch_assoc($remote_res);
      $isRemoteNow = ($remote_row && is_null($remote_row['stop_dt']));

      $hasVisit = is_array($row5);
      $in_dt = $hasVisit ? (string)$row5["in_dt"] : "";
      $eat_start_dt = $hasVisit ? (string)$row5["eat_start_dt"] : "";
      $eat_stop_dt = $hasVisit ? (string)$row5["eat_stop_dt"] : "";
      $out_dt = $hasVisit ? (string)$row5["out_dt"] : "";
      $hasOpenAddTime = is_array($row7);

      $time_in = ($in_dt !== "" && $in_dt !== $time) ? date("H:i", strtotime($in_dt)) : "";
      $time_out = ($out_dt !== "" && $out_dt !== $time) ? date("H:i", strtotime($out_dt)) : "";

      $isLunchPause = $hasVisit
        && $eat_start_dt !== ""
        && $eat_start_dt !== $time
        && ($eat_stop_dt === "" || $eat_stop_dt === $time);
      $hasGoneHome = $hasVisit
        && $in_dt !== ""
        && $in_dt !== $time
        && $out_dt !== ""
        && $out_dt !== $time;

      if ($isLunchPause || $hasOpenAddTime) {
        $img = "<img class=\"work-status\" data-emp=\"$id_empl\" title=\"Обед/приостановка времени\" src=\"img/pause_time.png\">";
        $presenceSortOrder = 1;
      } elseif ($isRemoteNow) {
        $img = "<img class=\"work-status presence-inline-icon\" data-emp=\"$id_empl\" title=\"Работает удаленно\" src=\"img/remoteWorkIcon2.png\">";
        $presenceSortOrder = 1;
      } elseif (!$hasVisit) {
        $img = "<img class=\"work-status presence-inline-icon\" data-emp=\"$id_empl\" title=\"На работу не приходил\" src=\"img/home.png\">";
        $presenceSortOrder = 0;
      } elseif ($hasGoneHome) {
        $img = "<img class=\"work-status\" data-emp=\"$id_empl\" title=\"Ушел домой\" src=\"img/go_home.png\">";
        $presenceSortOrder = 2;
      } else {
        $img = "<img class=\"work-status presence-inline-icon\" data-emp=\"$id_empl\" title=\"На рабочем месте\" src=\"img/in_work2.png\">";
        $presenceSortOrder = 1;
      }

      $employee_arr[] = array($full_name, $time_in, $time_out, $img, $in_dt, $out_dt, $phone_number, $personal_phone, $corporate_phone, $id_empl, $birthday, $email_empl, $presenceSortOrder);
    }

    $employee_arr = array_filter($employee_arr, function($item) {
      return !in_array($item[9], [400, 500, 501]);
    });

    function sort_employee ($a, $b) {
      $presenceComparison = (int)$a[12] <=> (int)$b[12];

      if ($presenceComparison !== 0) {
        return $presenceComparison;
      }

      return mb_strtolower($a[0], 'UTF-8') <=> mb_strtolower($b[0], 'UTF-8');
    }

    usort($employee_arr, "sort_employee");

    $employee_arr = array_merge($bosses_arr, $employee_arr); 

    function get_phone_info($id_empl, $phone, $personal_phone, $corporate_phone, $email_empl) {
      $tooltipId = 'u' . (int) $id_empl . '-contacts';
      $contacts = [];
      $contacts[] = "Телефон внутренний: " . htmlspecialchars($phone);

      switch (true) {
        case (!empty($corporate_phone) && !empty($personal_phone) && !empty($email_empl)):
          $contacts[] = "Мобильный: " . htmlspecialchars($personal_phone);
          $contacts[] = "Служебный мобильный: " . htmlspecialchars($corporate_phone);
          $contacts[] = "Эл. почта: " . htmlspecialchars($email_empl);
          break;

        case (!empty($corporate_phone)):
          $contacts[] = "Служебный мобильный: " . htmlspecialchars($corporate_phone);
          $contacts[] = "Эл. почта: " . htmlspecialchars($email_empl);
          break;
        
        case (!empty($personal_phone)): 
          $contacts[] = "Мобильный: " . htmlspecialchars($personal_phone);
          $contacts[] = "Эл. почта: " . htmlspecialchars($email_empl);

          break;

        case (!empty($email_empl)):
          $contacts[] = "Эл. почта: " . htmlspecialchars($email_empl);
          break;
      }

      if (!empty($contacts)) {
        echo '<div class="phone_tooltip" data-phone-tooltip-target="' . $tooltipId . '">';
        echo implode('<br>', $contacts);
        echo '</div>';
      }
    }

    $today = date('Y-m-d');

    function getHolidayDates ($link, $form_date = null) {
      if ($form_date === null) {
        $form_date = date('Y-m-d');
      }

      $holidays = [];

      $query = "SELECT date FROM work_dayoff WHERE type = 0 AND date >= ?";
      $stmt = mysqli_prepare($link, $query);
      mysqli_stmt_bind_param($stmt, 's', $form_date);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      while ($row = mysqli_fetch_assoc($result)) {
        $holidays[] = $row['date'];
      }

      foreach ($holidays as $hd) {
        echo "<!-- holiday: $hd -->";
      }
      return $holidays;
    }

    $holidayDates = getHolidayDates($link, $today);

    function getWorkingDaysUntil($today, $start_date, $holidays = []) {
      $start = new DateTime($today);
      $end = new DateTime($start_date);

      if ($start >= $end) {
        return 0;
      }

      $interval = new DateInterval('P1D');
      $period = new DatePeriod($start, $interval, $end);

      $workingDays = 0;

      foreach ($period as $date) {
        $dayOfWeek = $date->format('N'); // 6=суббота, 7=воскресенье
        $dateStr = $date->format('Y-m-d');

        $isWeekend = $dayOfWeek >= 6;
        $isHoliday = in_array($dateStr, $holidays);

        echo "<!-- Check $dateStr: weekend = " . ($isWeekend ? "yes" : "no") . ", holiday = " . ($isHoliday ? "yes" : "no") . "-->";

        if (!$isWeekend && !$isHoliday) {
          $workingDays++;
        }
      }
      return $workingDays;
    }

    function getDayWord ($number) {
      $number = (int)$number;
      $lastDate = $number % 10;
      $lastTwo = $number % 100;

      if ($lastTwo >= 11 && $lastTwo <= 14) {
        return 'дней';
      }

      if ($lastDate === 1) return 'день';
      if ($lastDate >= 2 && $lastDate <= 4) return 'дня';
      
      return 'дней';
    }

    function getDaysLeft($end_date, $today) {
      $todayDate = new DateTime($today);
      $endDate = new DateTime($end_date);

      $diff = $todayDate->diff($endDate)->days;

      return $diff + 1;
    }

    $eventsToday = [];

    $query9 = "
    SELECT user_id, event, start_date, stop_date 
    FROM staff_leaves 
    WHERE (
          (event = 'Больничный' AND ? BETWEEN start_date AND stop_date)
          OR 
          (event = 'Отпуск' AND (? BETWEEN start_date AND stop_date OR start_date >= ?))
          OR
          (event = 'Командировка' AND (? BETWEEN start_date AND stop_date OR start_date >= ?))
          )";
    $result9 = db_query($link, $query9, 'sssss', array($today, $today, $today, $today, $today));
    while ($row9 = mysqli_fetch_assoc($result9)) {
      $uid = $row9['user_id'];
      $event = $row9['event'];
      $start = $row9['start_date'];
      $stop = $row9['stop_date'];

      if (!isset($eventsToday[$uid])) $eventsToday[$uid] = [];

      $alreadyExists = false;
      foreach ($eventsToday[$uid] as $e) {
        if ($e['event'] === $event && $e['start_date'] === $start && $e['stop_date'] === $stop) {
          $alreadyExists = true;
          break;
        }
      }

      if (!$alreadyExists) {
        $eventsToday[$uid][] = [
          'event' => $event,
          'start_date' => $start,
          'stop_date' => $stop
        ];
      }
    }

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
                echo "<span class=\"current-day-stat-title\">Краткая статистика за текущий и предыдущие месяцы (" . (int)$monthCnt . ") ";
		echo "<img src=\"img/plus.bmp\" onclick=\"st_month_inc();\" />";
		echo "<img src=\"img/minus.bmp\" onclick=\"st_month_dec();\" />";
		echo "<img src=\"img/dva.bmp\" onclick=\"st_month_def();\" />";
                echo "</span>";
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
  echo "<div class=\"app-footer\">";
    include_once __DIR__ . "/end.php";
  echo "</div>";
echo "</div>";

?>

<script type="text/javascript" charset="utf-8"> 

build_in_delay_expl();
build_in_add_work();
get_time_registration_div_content();   

function update_clock(){
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat) 
  {
    if ( document.getElementById('dateTimeFieldNav') )
    {
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId = setInterval(update_clock, 10000);

</script> 

<?php
echo "</body>";
echo "</html>";
?>
