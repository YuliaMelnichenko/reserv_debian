<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userPass = $_COOKIE['T_O_R_I_PASSWORD'];

$userPass = trim($userPass);

if ( $userPass != "" )
{
  echo $userPass;
}
else
{
  echo "";
}
return;
?>