<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if ( ! isset( $_SESSION['stat_month_count'] ) )
{ 
  $_SESSION['stat_month_count'] = 2;
}		
else
{
  if ( $_SESSION['stat_month_count'] > 1 )
    $_SESSION['stat_month_count'] = $_SESSION['stat_month_count'] - 1;	
}
return $_SESSION['stat_month_count'];                        
?>