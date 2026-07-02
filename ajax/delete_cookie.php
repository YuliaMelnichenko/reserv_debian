<?php
  require_once __DIR__ . '/../inc/access.php';
  require_csrf_for_unsafe_request(true);

  header("Content-type: text/plain; charset=utf-8");
  header("Cache-Control: no-store, no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);

  $retSetName = setcookie( "T_O_R_I_USERNAME", "" );
  $retSetPass = setcookie( "T_O_R_I_PASSWORD", "" );

  if ( $retSetName == 1 AND $retSetPass == 1 )
  {
    echo "1";  
  }
  else
  {
    echo "0";
  }
  return;
?>
