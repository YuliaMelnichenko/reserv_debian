<?php
  require_once __DIR__ . '/../inc/access.php';
  require_csrf_for_unsafe_request(true);

ajax_text_headers();

  $expires = time() - 3600;
  $retSetName = setcookie(
    "T_O_R_I_USERNAME",
    "",
    app_cookie_options($expires, '/ajax')
  );
  $retSetPass = setcookie(
    "T_O_R_I_PASSWORD",
    "",
    app_cookie_options($expires, '/ajax')
  );
  $retSetLegacyPass = setcookie(
    "TORIPASSWORD",
    "",
    app_cookie_options($expires)
  );

  if ( $retSetName == 1 AND $retSetPass == 1 AND $retSetLegacyPass == 1 )
  {
    echo "1";  
  }
  else
  {
    echo "0";
  }
  return;
?>
