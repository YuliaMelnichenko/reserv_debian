<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( isset($_POST['login']) AND isset($_POST['passwd']) )
{
  $login = ($_POST['login']);
  $passwd = ($_POST['passwd']);

  $duration = time() + 3600 * 24 * 31;

  $retSetName = setcookie( "T_O_R_I_USERNAME", $login, $duration );
  $retSetPass = setcookie( "T_O_R_I_PASSWORD", $passwd, $duration );

  if ( $retSetName == 1 AND $retSetPass == 1 )
  {
    echo "$login 1";
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