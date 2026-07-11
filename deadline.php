<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" src="js/tory.js"></script> 
<script type="text/javascript" charset="utf-8"> 

check_pause_state();

function update_clock(){
  $.post('ajax/get_current_day_time.php', RetSWT);                           
  function RetSWT(dat) {
    if ( document.getElementById('dateTimeField') ){
      document.getElementById('dateTimeField').innerHTML = dat;
    }
  }
}

function st_month_inc(){	
  $.post('ajax/stat_month_inc.php', RetSWT);                           
  function RetSWT(dat) {
    window.location=self.location;
  }
}

function st_month_dec(){
  $.post('ajax/stat_month_dec.php', RetSWT);                           
  function RetSWT(dat) {
    window.location=self.location;
  }
}

function st_month_def(){
  $.post('ajax/stat_month_def.php', RetSWT);                           
  function RetSWT(dat) {
    window.location=self.location;
  }
}

function get_time_registration_div_content(){
  $.post('ajax/get_time_registration_div.php', RetSWT6 );
  function RetSWT6(dat6){
    if ( document.getElementById('time_registration_div') ){ document.getElementById('time_registration_div').innerHTML = dat6; }
  }
} 

var timeRegistrationRequestInProgress = false;

function switch_day_state(next, callback) {
  if (timeRegistrationRequestInProgress) {
    return;
  }

  timeRegistrationRequestInProgress = true;

  $.post(
    'ajax/switch_day_state.php',
    {
      next: next
    },
    function(dat) {
      timeRegistrationRequestInProgress = false;

      if (dat == 1 || dat == "1") {
        if (typeof callback === 'function') {
          callback();
        }
        else {
          get_time_registration_div_content();
        }

        build_in_delay_expl();
      }
      else {
        alert(dat);
        get_time_registration_div_content();
      }
    }
  ).fail(function(xhr) {
    timeRegistrationRequestInProgress = false;

    var message = "Ошибка изменения состояния рабочего дня.";

    if (xhr.responseText) {
      message += "\n\n" + xhr.responseText;
    }

    alert(message);
    get_time_registration_div_content();
  });
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

function reg_eat_stop(){
  switch_day_state( 1 );
}   

function add_expl(){
  $.post('ajax/get_explanation_head.php', RetSWT1);
  function RetSWT1(dat1) {
    if ( document.getElementById('delay_explanation_head') ){
      document.getElementById('delay_explanation_head').innerHTML=dat1;
      document.getElementById('delay_explanation_head').style.display='block';
    }
  }
}

function add_training_time(){
  $.post('ajax/get_add_gym_time.php', RetSWT1);
  function RetSWT1(dat1) { 
    if ( document.getElementById('delay_explanation_sport_time') ){
      document.getElementById('delay_explanation_sport_time').innerHTML = dat1;
      document.getElementById('delay_explanation_sport_time').style.display='block';
    }
  }
}

function close_add_sport_time(){
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

function as_add_time(){
  $.post('ajax/get_add_times.php', RetSWT1);
  function RetSWT1(dat1) { 
    if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
    if ( document.getElementById('delay_explanation_add_time') ){
      document.getElementById('delay_explanation_add_time').innerHTML = dat1;
      document.getElementById('delay_explanation_add_time').style.display='block';
    }

    $.post('ajax/get_add_time_notif_count.php', RetSWT2);
    function RetSWT2(dat2) { 
      if ( document.getElementById('notifBtn') ){ document.getElementById('notifBtn').innerHTML = dat2; }
    }
  }
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
  if ( document.getElementById('delay_explanation_add_time') ){ document.getElementById('delay_explanation_add_time').style.display='block'; }
}

function close_explanation_head(){
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
}

function build_in_delay_expl(){
  $.post('ajax/get_delay_explanation_build_in.php', RetSWT2 );
  function RetSWT2(dat2){
    if ( document.getElementById('delay_explanation_buildin') ){ document.getElementById('delay_explanation_buildin').innerHTML = dat2; }
    if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display='none'; }
  }
}

function build_in_add_work(){
  $.post('ajax/get_add_time_build_in.php', RetSWT2 );
  function RetSWT2(dat2){ 
    if ( document.getElementById('add_work_buildin') ){ document.getElementById('add_work_buildin').innerHTML = dat2; }
  }
}

function as_delay(){
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
  if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display='block'; }

  $.post('ajax/get_delay_explanation.php', {}, RetSWT2 );
  function RetSWT2(dat2){
    if ( document.getElementById('delay_explanation_delay') ){ 
      document.getElementById('delay_explanation_delay').innerHTML = dat2; 
      if ( document.getElementById('explAddInfo') ){
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
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body bgcolor=\"#ffffff\" >";

echo "<font size=\"0\" color=\"#DDDDDD\" face=\"Arial\"> !!! 2021.12.30 Опоздание может быть принято 'по уважительной причине', если тому виной стал транспортный коллапс значительного размера.</font><br><br>";
echo "<font size=\"0\" color=\"#EEEEEE\" face=\"Arial\">Для этого необходимо:</font><br>";
echo "<font size=\"0\" color=\"#EEEEEE\" face=\"Arial\">1) Сделать скриншот экрана Яндекс.Навигатора со своим маршрутом и до 10:00 прислать его в группу ТОРИ в WhatsApp.</font><br>";
echo "<font size=\"0\" color=\"#EEEEEE\" face=\"Arial\">&emsp;На скриншоте должно быть видно:</font><br>";
echo "<font size=\"0\" color=\"#EEEEEE\" face=\"Arial\">&emsp;&emsp;а) Пройденный путь, оставшийся путь и текущее местоположение</font><br>";
echo "<font size=\"0\" color=\"#EEEEEE\" face=\"Arial\">&emsp;&emsp;б) Наличие огромной пробки, в которую Вы внезапно въехали</font><br>";
echo "<font size=\"0\" color=\"#EEEEEE\" face=\"Arial\">2) В условиях отсутствия транспорного коллапса, расчётное время прибытия из текущего местоположения в офис должно быть ранее, чем 10:00.</font><br>";

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


echo "<div id=\"delay_explanation_add_time\" >";
echo "</div>";

echo "<div id=\"delay_explanation_delay\">";
echo "</div>";
                                                              
echo "<div align=\"left\">";

////////////////////////////////////////////////////////
include_once __DIR__ . "/funcs.php";
include __DIR__ . "/short_stat.php";

save_last_location( "index.php" );
auth();

////////////////////////////////////////////////////////

  include_once __DIR__ . "/start.php";


  include __DIR__ . "/php_tori/connect.php";
  if ( isset( $_SESSION['ss_id'] ) ){ 
    $user_id = $_SESSION['ss_id'];
    $user_rate = $_SESSION['ss_rate'];
    $user_defaultStartTime = $_SESSION['ss_defaultStartTime'];
    $user_defaultStartHour = $_SESSION['ss_defaultStartHour'];
    $user_defaultStartMinute = $_SESSION['ss_defaultStartMinute'];
    $user_allowedDelay = $_SESSION['ss_allowedDelay'];
    $_date = date('Y-m-d');

    mysqli_set_charset($link, "utf8");
    $query0 = mysqli_query($link, "SELECT * FROM employees WHERE id = '$user_id'"); 
    $vn0 = mysqli_num_rows($query0);

    echo "<table cellpadding=\"10\" cellspacing=\"0\" border=1>";
    echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 120>";

    include_once __DIR__ . "/navigate.php";

    echo "</td>";               

    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 500>";

    echo "<h5 class=\"dark\">/текущий день</h5>";
 
    //-----------------------------------------------------------------------------------------------------------------
    
    if ( $vn0 == 1 ){
      $row0 = mysqli_fetch_assoc($query0);

      $empl_state = $row0["STATE"];

      $sv_name = get_sv_name_by_userid( $user_id );

      mysqli_set_charset($link, "utf8");
    
      $query01 = mysqli_query($link, "SELECT * FROM departments WHERE ID IN (SELECT DEPID FROM GROUPS WHERE USERID = '$user_id')"); 

      $row01 = mysqli_fetch_assoc($query01);

      $depName = $row01["NAME"];

      $room = $row01["ROOM"];     

      echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
      echo "<tr>";
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 520>";

      $width00 = 480;  
      $width11 = 200; 
      $width22 = $width00 - $width11; 

      echo "<table class=\"slim\" border=1>";
        echo "<tr>";
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $width11>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\">Сотрудник"."</font>";
          echo "</td>";  
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"center\" width = $width22>";
      echo "<font size=\"2\" color=\"#000000\" face=\"Arial\"><b>" . html_escape($row0["SURNAME"] . " " . $row0["FIRSTNAME"] . " " . $row0["LASTNAME"]) . "</b></font><br>";
          echo "</td>";  
        echo "</tr>";
        echo "<tr>";
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $width11>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\">Длительность рабочей недели"."</font>";
          echo "</td>";  
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = $width22>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\"><b>".$user_rate." ч.</b></font><br>";
          echo "</td>";  
        echo "</tr>";
        echo "<tr>";
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $width11>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\">Начало рабочего дня"."</font>";
          echo "</td>";  
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = $width22>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\"><b>".$user_defaultStartTime."</b></font><br>";
          echo "</td>";  
        echo "</tr>";
        echo "<tr>";
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $width11>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\">Допустимое опоздание"."</font>";
          echo "</td>";  
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = $width22>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\"><b>".$user_allowedDelay." мин.</b></font><br>";
          echo "</td>";  
        echo "</tr>";
        echo "<tr>";
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $width11>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\">Подразделение"."</font>";
          echo "</td>";  
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = $width22>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\"><b>".$row01["NAME"]." (".$row01["ROOM"]." к.)"."</b></font><br>";
          echo "</td>";  
        echo "</tr>";
        echo "<tr>";
          echo "<td class=\"nopadding_s\" vbgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $width11>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\">Ответственный"."</font>";
          echo "</td>";  
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"center\" width = $width22>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\"><b>".$sv_name."</b></font><br>";
          echo "</td>";  
        echo "</tr>";
      echo "</table>";

      echo "<div id=\"delay_explanation_buildin\">";
      echo  "</div>";

      echo "<div id=\"add_work_buildin\">";
      echo  "</div>";

      echo "<br><br>";

      echo "<table class=\"slim\" border=0>";
        echo "<tr>";
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 90>";
            echo "<font size=\"2\" color=\"#000000\" face=\"Arial\">Дата и время: "."</font>";
          echo "</td>";
          echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
            echo "<div id=\"dateTimeField\">";
              echo "<font size=\"4\" color=\"#000000\" face=\"Arial\">";
              echo "<b>".date("Y-m-d")." ".date("H:i:s")."</b>";
              echo "</font>";
            echo "</div>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    
      echo "</td>";
      echo "</tr>";
      echo "</table>";    

      echo "<font size=\"3\" color=\"#000000\" face=\"Arial\">";

      if ( isset( $_SESSION['ss_state'] ) )
      {
      }
      else{ 
	 $_SESSION['ss_state'] = 1;
      }   

    } 
      
    echo "<div id=\"time_registration_div\">";
    echo "</div>";

    echo "</td>";

    if ( $_SESSION['ss_id'] == 3000 ){

      $dateMonth = date('Y-m-d');

      $dateMonth = set_to_first_month_day( $dateMonth );

      if ( ! isset( $_SESSION['stat_month_count'] ) ){
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

	  for ( $monthNum = 0; $monthNum  < $_SESSION['stat_month_count']; $monthNum ++ ){
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
    include_once __DIR__ . "/end.php";
  echo "</font>";
echo "</div>";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" charset="utf-8"> 

build_in_delay_expl();
build_in_add_work();
get_time_registration_div_content();   

var timerId=setInterval( "update_clock()", 5000 );

</script> 

<?php
echo "</body>";
echo "</html>";  
?>
