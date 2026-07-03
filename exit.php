<?php
  ob_start();

  require_once __DIR__ . '/inc/session.php';

  $expires = time() - 3600;
  setcookie("T_O_R_I_PASSWORD", "", app_cookie_options($expires, '/ajax'));
  setcookie("T_O_R_I_USERNAME", "", app_cookie_options($expires, '/ajax'));
  setcookie("TORI_CSRF_TOKEN", "", app_cookie_options($expires, '/', false, 'Strict'));

  $sessionCookie = session_get_cookie_params();
  setcookie(
    session_name(),
    "",
    app_cookie_options(
      $expires,
      $sessionCookie['path'] ?: '/',
      true,
      isset($sessionCookie['samesite']) ? $sessionCookie['samesite'] : 'Lax',
      $sessionCookie['domain']
    )
  );

  $_SESSION = array();
  session_destroy();

  header("Location: index.php");
  exit();
?>
