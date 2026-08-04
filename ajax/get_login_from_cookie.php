<?php
require_once __DIR__ . '/../inc/ajax_response.php';
require_once __DIR__ . '/../inc/request.php';
ajax_text_headers();

$userName = trim(request_cookie_string('T_O_R_I_USERNAME'));

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
