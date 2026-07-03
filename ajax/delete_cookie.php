<?php
  require_once __DIR__ . '/../inc/access.php';
  require_csrf_for_unsafe_request(true);

  header("Content-type: text/plain; charset=utf-8");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);

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
