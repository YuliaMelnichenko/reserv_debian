<?php
session_start();

header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

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