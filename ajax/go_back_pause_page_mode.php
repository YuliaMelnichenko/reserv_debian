<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if ( $_SESSION['pause_page_mode'] > 1 )
{
  $_SESSION['pause_page_mode'] = $_SESSION['pause_page_mode'] - 1;
}
?>                                                                   