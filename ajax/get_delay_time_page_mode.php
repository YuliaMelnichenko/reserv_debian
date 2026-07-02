<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( isset( $_SESSION['delay_time_page_mode'] ) )
{
  echo $_SESSION['delay_time_page_mode'];
}
else
{
  echo 1;
}
?>                                                                   