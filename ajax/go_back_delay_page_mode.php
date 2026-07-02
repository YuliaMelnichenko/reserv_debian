<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if ( $_SESSION['delay_page_mode'] > 1 )
{
  $_SESSION['delay_page_mode'] = $_SESSION['delay_page_mode'] - 1;
}
$_SESSION['delay_page_recID'] = -1;
?>                                                                   