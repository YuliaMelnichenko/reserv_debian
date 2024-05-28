<?php
session_start();
ob_start();
include_once "/var/www/tori/start.php";
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" src="js/tory.js"></script> 
<script type="text/javascript" charset="utf-8"> 

var timerIdSessValid=setInterval( "check_sess()", 3000 );

function check_sess(){
  $.post('ajax/check_session_valid.php', RetSWT);
  function RetSWT(dat) {
    if ( dat == 0 ){
      window.location=self.location;
    }
  }
}

function check_day_change(){
  document.getElementById('layer_div').style.display='none';
  document.getElementById('layer_question_div').style.display='none';

  $.post('ajax/check_day_change.php', RetSWT);
  function RetSWT(dat){
    if ( dat == 1 ){
      clearInterval( timerIdDayChange );   
      document.getElementById('layer_div').style.display='none';
      document.getElementById('layer_question_div').style.display='none';
    }
  }
}

var timerIdDayChange=setInterval( "check_day_change()", 3000 );

function day_continue_confirm()
{
  $.post('ajax/day_continue_confirm.php', RetSWT);                           
  function RetSWT(dat) 
  {
    document.getElementById('layer_div').style.display='none';
    document.getElementById('layer_question_div').style.display='none';

    window.location=self.location;
  }   
}

function day_continue_reject()
{
  $.post('ajax/day_continue_reject.php', RetSWT);                           
  function RetSWT(dat) 
  {
    document.getElementById('layer_div').style.display='none';
    document.getElementById('layer_question_div').style.display='none';

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

function switch_day_state( next )
{
  $.post('ajax/switch_day_state.php', { next: next }, RetSWT);                           
  function RetSWT(dat) 
  { 
    if ( dat == 1 )
    {
      get_time_registration_div_content();
    }
    else
    {
      alert( dat );
    }
  }
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

function reg_eat_start()
{
  switch_day_state( 1 );
}

function reg_eat_stop()
{
  switch_day_state( 1 );
}   

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

function enter_out_time()
{
    $.post('ajax/get_out_time.php', RetSWT1);
  function RetSWT1(dat1) 
  {
    if ( document.getElementById('delay_out_time') )
    {
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
    if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display='none'; }
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
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/style.css\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body onload=\"check_day_change();\" bgcolor=\"#ffffff\" >";

// session_start();

include_once "/var/www/tori/funcs.php";
include "/var/www/tori/php_tori/connect.php";

$currentDate = get_current_datetime_in_timezone_str( 1, 0 );
$user_dayTransitionTime = $_SESSION['$ss_dayTransitionTime'];

$timeArr = datetimestr_to_day_start_stop_DT_ex_str( $currentDate, $user_dayTransitionTime );

$startDTOuter = $timeArr[0];
$stopDTOuter = $timeArr[1];
$transTimeBefore = $timeArr[2];
$transTimeAfter = $timeArr[3];

echo "<div id=\"layer_div\" class=\"layer_div\">";
echo "</div>";

echo "<div id=\"layer_question_div\" class=\"layer_question_div_2\" style=\"display:none;\">";
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
                  echo "<button style=\"cursor: pointer; font-size: 80%; width:180px; height:30px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"day_continue_confirm();\">Ok</button>";                   
                echo "</td>";
                echo "<td class=\"report_no_padding_no_border_no_bg\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = 198px>";
                  echo "<button style=\"cursor: pointer; font-size: 80%; width:180px; height:30px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"day_continue_reject();\">Oтмена</button>";                   
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
include_once  "/var/www/tori/funcs.php";

$ip = $_SERVER['REMOTE_ADDR'];
auth();

if ( $_SESSION['ss_id'] == 500 || $_SESSION['ss_id'] == 501 )
{
  header("Location: my_report.php");
  exit(); 
}

////////////////////////////////////////////////////////

  include "/var/www/tori/php_tori/connect.php";
  if ( isset( $_SESSION['ss_id'] ) )
  { 
    $user_id = $_SESSION['ss_id'];
    $user_rate = $_SESSION['ss_rate'];
    $user_defaultStartTime = $_SESSION['ss_defaultStartTime'];
    $user_defaultStartHour = $_SESSION['ss_defaultStartHour'];
    $user_defaultStartMinute = $_SESSION['ss_defaultStartMinute'];
    $user_allowedDelay = $_SESSION['ss_allowedDelay'];
    $user_timeZone = $_SESSION['ss_UserTimeZoneStr'];
    $user_defaultStartTimeWithDelay = $_SESSION['ss_defaultStartTimeWithDelay'];
    $user_RemoteWork = $_SESSION['ss_RemoteWork'];
    $user_RemoteWorkStr = $_SESSION['ss_RemoteWorkStr'];
    $user_dayTransitionTime = $_SESSION['$ss_dayTransitionTime'];

    $currentDate = get_current_datetime_in_timezone_str( 1, 0 );

    $dateArr = datetimestr_to_day_start_stop_DT_ex_str_idx( $currentDate, $user_dayTransitionTime );  

    $startDTStr = $dateArr[0];
    $stopDTStr = $dateArr[1];    
    
    $_SESSION['ss_startDTStr'] = $startDTStr;
    $_SESSION['ss_stopDTStr'] = $stopDTStr;
    
    $_date = date('Y-m-d');

    mysqli_set_charset($link, "utf8");
    $query0 = mysqli_query($link, "SELECT * FROM employees WHERE id = '$user_id'");
    $row0 = mysqli_fetch_assoc($query0); 
    $vn0=mysqli_num_rows($query0);

    echo "<table>";
    echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";

    include_once  "/var/www/tori/navigate.php";

    echo "</td>";               

    $wholeWidth = 625;

    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

    echo "<h5 class=\"dark\"><br>/текущий день<br><br></h5>";
        
    if ( $vn0 == 1 )
    {
      $empl_state = $row0["state"];                                                  
            
      $sv_name = get_sv_name_by_userid( $user_id );

      mysqli_set_charset($link, "utf8");
    
      $query01 = mysqli_query($link, "SELECT * FROM DEPARTMENTS WHERE ID IN (SELECT DEPID FROM GROUPS WHERE USERID = '$user_id')"); 

      $row01 = mysqli_fetch_assoc($query01);

      $depName = $row01["NAME"];

      $room = $row01["ROOM"];     

      echo "<table>";
      echo "<tr>";
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\">";

      $width00 = 600;  
      $width11 = 320; 
      $width22 = $width00 - $width11; 

      echo "<table>";
        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\" width = $width11>";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">Сотрудник</span>";
          echo "</td>";  
          echo "<td class=\"brd\" valign=\"top\" align=\"center\" width = $width22>";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">".$row0["surname"]." ".$row0["firstname"]." ".$row0["lastname"]."</span>";  
          echo "</td>";  
        echo "</tr>";     

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">Подразделение</span>"; 
          echo "</td>";  
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">".$depName." (".$room." к.)"."</span>";
          echo "</td>";  
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
          echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">Ответственный</span>";  
          echo "</td>";  
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">".$sv_name."</span>";
          echo "</td>";  
        echo "</tr>";     

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">Длительность рабочей недели</span>";
          echo "</td>";  
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">".$user_rate." ч.</span>";
          echo "</td>";  
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">Начало рабочего дня c допустимым опозданием</span>";
          echo "</td>";  
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            if ( $user_RemoteWork == 1 )
            {
              echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">---</span>";
            }
            else
            {
              echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">".$user_defaultStartTime." >> ".$user_defaultStartTimeWithDelay." (+".$user_allowedDelay." мин.)</span>";
            }
          echo "</td>";  
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">Часовой пояс</span>";
          echo "</td>";  
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">".$user_timeZone."</span>";
          echo "</td>";  
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">Текущий отчетный период</span>";
          echo "</td>";  
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">".$startDTStr." - ".$stopDTStr."</span>";
          echo "</td>";  
        echo "</tr>";

        echo "<tr>";
          echo "<td class=\"brd\" valign=\"top\" align=\"left\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">Режим работы</span>";
          echo "</td>";  
          echo "<td class=\"brd\" valign=\"middle\" align=\"center\">";
            echo "<span style=\"color:#000000; font-family: Arial; font-size: 13px; font-weight: 500\">".$user_RemoteWorkStr."</span>";
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
 
    echo "<td bgcolor=\"#f0f7fb\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 500>";
    echo "<h5 class=\"dark0\"><br>/обновления кнопки 10.04.2024г.:<br><br></h5>";
    echo "<h5 class=\"dark1\">1. Возможность изменить время ухода за предыдущий рабочий день при незакрытом рабочем дне в период с 9:00 до 11:30.<br></h5>";
    echo "<h5 class=\"dark1\">2. Настроено отображение времени прихода/ухода на рабочее место и начало/конца обеденного времени, при наведении курсора на время во временном отчете.<br></h5>";
    echo "<h5 class=\"dark1\">3. Настроено отображение присутствия сотрудников на рабочем месте иконкой <img title=\"еще на работе\" src=\"img/inwork.png\"><br></h5>";
    echo "<h5 class=\"dark1\">4. Возможность менять время прихода с обеда за предыдущий рабочий день, если время прихода с обеда не зафиксировано.<br></h5>";
    echo "<h5 class=\"dark1\">5. Добавлена кнопка регистрации ухода в тренажерный зал. (функционал приостановки времени).<br></h5>";
    echo "<h5 class=\"dark1\">6. Добавлена кнопка \"Тренажерный зал\" в панели навигации. В данной вкладке отображается список сотрудников, присутствующих в данный момент в спортивном зале. <br></h5>";
    echo "<h5 class=\"dark1\">7. Добавлена возможность записаться в спортзал во вкладке \"Тренажерный зал\".<br></h5>";
    // echo "<h5 class=\"dark2\">-реализован автомасштаб блока отображения отчета<br></h5>";
    // echo "<h5 class=\"dark1\">7. оптимизация интерфейса<br></h5>";
    // echo "<h5 class=\"dark1\">8. при незакрытом рабочем дне и при смене отчетного периода (по-умолчанию, суток) в течение 3-х часов и при подтверждении пользователем имеется возможность закрыть предудущий день окончанием предыдущего периода и начать новый рабочий день началом нового периода</h5>";
    // echo "<h5 class=\"dark1\"><br><br> для настройки смещения начала суток, часового пояса, режима работы и др. обращайтесь к администратору системы<br></h5>";
//    echo "<h5 class=\"red3\"><br><br>предыдущая версия системы (без возможности регистрации времени присутствия) доступна по адресу <a href=\"http://192.168.100.6/tmp/my_report.php\" class=\"mlbig\">http://192.168.100.6/tmp/my_report.php</a><br></h5>";
                                   
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
    include_once  "/var/www/tori/end.php";
  echo "</font>";
echo "</div>";

?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" charset="utf-8"> 

build_in_delay_expl();
build_in_add_work();
get_time_registration_div_content();   

function update_clock()
{
  $.post('ajax/get_current_day_time.php', RetSWT);                           
  function RetSWT(dat) 
  {
    if ( document.getElementById('dateTimeFieldNav') )
    {
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId=setInterval( "update_clock()", 10000 );

</script> 

<?php
echo "</body>";
echo "</html>";  
?>