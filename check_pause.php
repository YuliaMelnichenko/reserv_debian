<?php
ob_start();

session_start();
echo "<div id=\"pause_div\">";
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/style.css\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" charset="utf-8"> 

function auth()
{	
	var login  = document.getElementById('login').value;
	var passwd = document.getElementById('passwd').value;

	$.post('ajax/auth.php', {login: login, passwd: passwd}, RetSWT);

  function RetSWT(dat) 
	{  
//alert( 2 );
		if ( dat.length > 100 )
			alert( dat );
      window.location=self.location;
	  }  
}

function set_focus()
{	
	document.getElementById("auth_btn").focus();
}
</script>

<?php
echo "<body bgcolor=\"#ffffff\" onload=\"set_focus();\">";                                                              
echo "<div align=\"center\">";

$ip = $_SERVER['REMOTE_ADDR'];

if ( $ip == "192.168.100.50" or $ip == "192.168.100.69" )
{ 
  $_SESSION['ss_id'] = -1; 
  move_to_last_location(); 
}

if ( !isset($_SESSION['ss_id']) )
{
  $_SESSION['ss_mode'] = 0;
  $first_num = rand(1,20);
  $second_num = rand(1,20);

  $_SESSION['ss_check_result'] = $first_num + $second_num;
  $summ_ = $_SESSION['ss_check_result'];

  echo "<h6>Учет времени присутствия сотрудников. <br></h1>";

  echo "<table cellpadding=\"10\" cellspacing=\"0\" border=0>";
  echo "<tr>";
  echo "<td bgcolor=\"#ffffff\"  valign=\"top\" align=\"left\" width = 440>";

  echo "<table cellpadding=\"10\" cellspacing=\"0\" border=1>";
  echo "<tr>";
  echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 430>";

  echo "<h4>Для продолжения необходима авторизация</h3>";

  echo "<font size=\"3\" color=\"#222222\" face=\"Arial\">Логин: </font><input type=\"text\" value=\"\" id=\"login\" style=\"width:120px;\" />";
  echo "<font size=\"3\" color=\"#222222\" face=\"Arial\"> Пароль: </font><input type=\"password\" value=\"\" id=\"passwd\" style=\"width:120px;\" /><br />";
  echo "<input type=\"hidden\" value=\"$summ_\" name=\"check\" style=\"width:30px;\" /><br />";
  echo "<button id=\"auth_btn\" style=\"font-size: 150%; width:420px; height:50px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"auth();\" name=\"nextBtn\">Авторизоваться</button>";
  
  echo "</td>";
  echo "</tr>";
  echo "</table>";

  echo "<a href=\"register.php\" class=\"ml\" title=\"Регистрация\">регистрация</a>";

  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</div>";
}
else
{
  move_to_last_location();
}

echo "</div>";

echo "</body>";
echo "</html>";  
?>