<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
csrf_ensure_token();

include_once __DIR__ . "/funcs.php";
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
<script type="text/javascript" src="js/tory.js?v=20260709"></script>
<script type="text/javascript" charset="utf-8"> 

var toriCsrfToken = <?php echo json_encode(csrf_ensure_token()); ?>;

function check_cookie()
{
  $.post('ajax/get_login_from_cookie.php', RetSWT1 );
  function RetSWT1(dat1) 
  {
    if ( dat1 != "" )
    {
      document.getElementById('login').value = dat1;
    }
  }
}

function auth() {
  var login  = document.getElementById('login').value;
  var passwd = document.getElementById('passwd').value;

  if ( document.getElementById('autologin').checked ) {
    $.post('ajax/set_cookie.php', {login: login, _csrf: toriCsrfToken}, RetSWT1 );
    function RetSWT1(dat1) 
    {
      if ( dat1 == 0 )
      {
        alert( "Ошибка сохранения логина. Проверьте настройки или смените браузер" );
      }
    }
  }
  else {
    unset_cookie(toriCsrfToken);
  }

  $.post('ajax/auth.php', {login: login, passwd: passwd, _csrf: toriCsrfToken}, function(dat) {
    console.log("Server answer: ", dat);
    if (dat.trim() === "OK") {
      window.location = self.location;
    } else {
      alert("Error: " + dat );
      unset_cookie(toriCsrfToken);
      document.getElementById('login').value = '';
      document.getElementById('passwd').value = '';
      // document.getElementById('autologin').checked = false;
    }
  });
}

function set_focus()
{	
  document.getElementById("auth_btn").focus();
}
</script>
<?php
echo "<body bgcolor=\"#ffffff\" onload=\"set_focus();\">";
                                                              
echo "<div align=\"center\">";

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
  echo "<td bgcolor=\"#ffffff\"  valign=\"top\" align=\"left\" width = 460>";

  echo "<table cellpadding=\"10\" cellspacing=\"0\" border=1>";
  echo "<tr>";
  echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 460>";

  echo "<h4>Для продолжения необходима авторизация</h4><br><br>";

  echo "<font size=\"3\" color=\"#222222\" face=\"Arial\">Логин: </font><input type=\"text\" id=\"login\" style=\"width:120px;\" />";
  echo "<font size=\"3\" color=\"#222222\" face=\"Arial\"> Пароль: </font><input type=\"password\" id=\"passwd\" style=\"width:170px;\" /><br />";

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
        echo "<h5 class=\"middle\">запомнить логин</h5>";
      echo "</td>";
    echo "</tr>";
    echo "<tr>";
      echo "<td height = 10>";
      echo "</td>";
      echo "<td height = 10>";
      echo "</td>";
    echo "</tr>";
  echo "</table>";

  echo "<input type=\"hidden\" value=\"$summ_\" name=\"check\" style=\"width:30px;\" />";
  echo "<button id=\"auth_btn\" style=\"font-size: 150%; width:420px; height:50px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"auth();\" name=\"nextBtn\">Авторизоваться</button>";
  echo "</td>";
  echo "</tr>";
  echo "</table>";


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

</script> 

<?php
echo "</body>";
echo "</html>";  
?>

