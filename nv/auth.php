<?
ob_start();

session_start();

$ip=$_SERVER['REMOTE_ADDR'];

echo $ip;


include_once "funcs.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/style.css\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
?>
<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" src="js/tory.js"></script> 
<script type="text/javascript" charset="utf-8"> 

function check_cookie()
{
  $.post('ajax/get_login_from_cookie.php', RetSWT1 );
  function RetSWT1(dat1) 
  {
    //alert(dat1);    
    if ( dat1 != "" )
    {
      $.post('ajax/get_passwd_from_cookie.php', RetSWT2 );
      function RetSWT2(dat2) 
      {
        //alert(dat2);    
        if ( dat2 != "" )
        {
          document.getElementById('login').value = dat1;
          document.getElementById('passwd').value = dat2;
          auth();
        }
      }    
    }
  }
}

function auth()
{
  if ( document.getElementById('autologin').checked )
  {

    var login  = document.getElementById('login').value;
    var passwd = document.getElementById('passwd').value;

    $.post('ajax/set_cookie.php', {login: login, passwd: passwd}, RetSWT1 );
    function RetSWT1(dat1) 
    {   // alert(dat1);
      if ( dat1 == 0 )
      {
        alert( "Ошибка сохранения авторизационных данных. Проверьте настройки или смените браузер" );
      }
    }
  }

  $.post('ajax/auth.php', {login: login, passwd: passwd}, RetSWT);
  function RetSWT(dat) 
  {  
    if ( dat.length > 100 )
    {
      alert( dat );
      unset_cookie();
      //check_cookie();
    }
    window.location=self.location;
  }   
}

function set_focus()
{	
  document.getElementById("auth_btn").focus();
}
</script>
<?
echo "<body bgcolor=\"#ffffff\" onload=\"set_focus();\">";
#echo "<table background=\"tori.jpg\"><tr><td>";



///echo "555 = ".$_SESSION['ss_id'];
                                                              
echo "<div align=\"center\">";

$ip = $_SERVER['REMOTE_ADDR'];

/*if ( $ip == "192.168.100.50" or $ip == "192.168.100.69" or $ip == "192.168.100.167"  or $ip == "192.168.100.54" )
{ 
  $_SESSION['ss_id'] = 500; 
  move_to_last_location(); 
} */

/*if ( $ip == "192.168.100.54" )
{ 
  $_SESSION['ss_id'] = 501; 
  move_to_last_location(); 
} */

//echo "userID = ".$_SESSION['ss_id'];

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

  echo "<h4>Для продолжения необходима авторизация</h4><br><br>";

//  echo "<form>";
  echo "<font size=\"3\" color=\"#222222\" face=\"Arial\">Логин: </font><input type=\"text\" value=\"\" id=\"login\" style=\"width:120px;\" />";
  echo "<font size=\"3\" color=\"#222222\" face=\"Arial\"> Пароль: </font><input type=\"password\" value=\"\" id=\"passwd\" style=\"width:170px;\" /><br />";

  echo "<table border=0>";
    echo "<tr>";
      echo "<td height = 10>";
      echo "</td>";
      echo "<td height = 10>";
      echo "</td>";
    echo "</tr>";
    echo "<tr>";
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 20>";
        echo "<input class=\"no_padding\"  checked style=\"font-size: 100%; width:14px; height:14px; background-color:#ddeeff; border:0px solid #888888;\" type=\"checkbox\" id=\"autologin\" value=\"1\" >";
      echo "</td>";
      echo "<td bgcolor=\"#ddeeff\" valign=\"top\" align=\"left\" width = 400>";
        echo "<h5 class=\"middle\">запомнить</h5>";
      echo "</td>";
    echo "</tr>";
    echo "<tr>";
      echo "<td height = 10>";
      echo "</td>";
      echo "<td height = 10>";
      echo "</td>";
    echo "</tr>";
  echo "</table>";

  //echo "<input checked style=\"font-size: 100%; width:14px; height:14px; background-color:#ddeeff; border:0px solid #888888;\" type=\"checkbox\" id=\"autologin\" value=\"1\" ><h5 class=\"small\">запомнить</h5>";

  #echo "Чему равна сумма ".$first_num." и ".$second_num." ? ";
  echo "<input type=\"hidden\" value=\"$summ_\" name=\"check\" style=\"width:30px;\" />";
  //echo "<input type=\"submit\" style=\"font-size: 150%; width:420px; height:50px; background-color:#f8d888; border:1px solid #888888;\" value=\"Авторизоваться\"/>";
  echo "<button id=\"auth_btn\" class=\"$button_style_1\" style=\"font-size: 150%; width:420px; height:50px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"auth();\" name=\"nextBtn\">Авторизоваться</button>";
//  echo "</form>";  
  
  echo "</td>";
  echo "</tr>";
  echo "</table>";

 // echo "<a href=\"register.php\" class=\"ml\" title=\"Регистрация\">регистрация</a>";

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

?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" charset="utf-8"> 

check_cookie();
//auth();

</script> 

<?

echo "</body>";
echo "</html>";  
?>

