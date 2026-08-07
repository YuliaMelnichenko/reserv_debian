<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';

include_once __DIR__ . "/funcs.php";
$isAuthenticated = access_session_is_valid();
csrf_ensure_token();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
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

  var rememberLogin = document.getElementById('autologin').checked ? '1' : '0';

  $.post('ajax/auth.php', {
    login: login,
    passwd: passwd,
    remember_login: rememberLogin,
    _csrf: toriCsrfToken
  }, function(dat) {
    if (dat.trim() === "OK") {
      window.location = 'index.php';
    } else {
      alert("Error: " + dat );
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

if ( !$isAuthenticated )
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

  echo "<span class=\"auth-label\">Логин: </span><input class=\"auth-login-input\" type=\"text\" id=\"login\" />";
  echo "<span class=\"auth-label\"> Пароль: </span><input class=\"auth-password-input\" type=\"password\" id=\"passwd\" /><br />";

  echo "<table border=0>";
    echo "<tr>";
      echo "<td height = 10>";
      echo "</td>";
      echo "<td height = 10>";
      echo "</td>";
    echo "</tr>";
    echo "<tr>";
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 20>";
        echo "<input class=\"no_padding auth-checkbox\" checked type=\"checkbox\" id=\"autologin\" value=\"1\" >";
      echo "</td>";
      echo "<td bgcolor=\"#ddeeff\" valign=\"top\" align=\"left\" width = 400>";
        echo "<h5 class=\"middle\">оставаться в системе на этом устройстве</h5>";
      echo "</td>";
    echo "</tr>";
    echo "<tr>";
      echo "<td height = 10>";
      echo "</td>";
      echo "<td height = 10>";
      echo "</td>";
    echo "</tr>";
  echo "</table>";

  echo "<input type=\"hidden\" value=\"$summ_\" name=\"check\" />";
  echo "<button id=\"auth_btn\" class=\"auth-submit-button\" onclick=\"auth();\" name=\"nextBtn\">Авторизоваться</button>";
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

