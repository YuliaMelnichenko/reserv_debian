<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if ( isset( $_SESSION['pause_page_mode'] ) )
{
  echo $_SESSION['pause_page_mode'];
}
else
{
  echo 1;
}                            
?>                                                                   