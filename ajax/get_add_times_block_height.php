<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( isset( $_SESSION['add_times_size'] ) )
{
  echo $_SESSION['add_times_size'];
}
else
{
  echo 105;
}
?>