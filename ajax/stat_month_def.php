<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$_SESSION['stat_month_count'] = 2;

return $_SESSION['stat_month_count'];                        
?>