<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_csrf_for_unsafe_request(true);

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( isset($_POST['login']) AND isset($_POST['passwd']) )
{
  $login = ($_POST['login']);
  $passwd = ($_POST['passwd']);

  $duration = time() + 3600 * 24 * 31;

  $retSetName = setcookie(
    "T_O_R_I_USERNAME",
    $login,
    app_cookie_options($duration, '/ajax')
  );
  $retSetPass = setcookie(
    "T_O_R_I_PASSWORD",
    $passwd,
    app_cookie_options($duration, '/ajax')
  );

  if ( $retSetName == 1 AND $retSetPass == 1 )
  {
    echo "1";
  }
  else
  {
    echo "0";
  }
} 
else
{
  echo "0";
}
return; 
?>
