<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if ( $_SESSION['add_time_page_mode'] > 1 )
{
  $_SESSION['add_time_page_mode'] = $_SESSION['add_time_page_mode'] - 1;
}
$_SESSION['add_time_page_recID'] = -1;
?>                                                                   