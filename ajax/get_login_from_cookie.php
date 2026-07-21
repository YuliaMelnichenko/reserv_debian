<?php
require_once __DIR__ . '/../inc/ajax_response.php';
ajax_text_headers();

$userName = trim((string) ($_COOKIE['T_O_R_I_USERNAME'] ?? ''));

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
