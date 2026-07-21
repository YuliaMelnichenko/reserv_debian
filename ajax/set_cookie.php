<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_csrf_for_unsafe_request(true);

ajax_text_headers();

if ( isset($_POST['login']) )
{
  $login = trim((string) $_POST['login']);

  $duration = time() + 3600 * 24 * 31;

  $retSetName = setcookie(
    "T_O_R_I_USERNAME",
    $login,
    app_cookie_options($duration, '/ajax')
  );

  if ( $retSetName == 1 )
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
