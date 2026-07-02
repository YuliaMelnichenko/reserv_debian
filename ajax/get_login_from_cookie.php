<?php
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userName = $_COOKIE['T_O_R_I_USERNAME'];

$userName = trim($userName);

if ( $userName != "" )
{
  echo $userName;
}
else
{
  echo "";
}
return;
?>