<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if ( isset( $_SESSION['add_times_size'] ) )
{
  echo $_SESSION['add_times_size'];
}
else
{
  echo 105;
}
?>