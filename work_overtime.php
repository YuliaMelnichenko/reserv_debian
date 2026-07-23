<?php 
ob_start();
require_once __DIR__ . '/inc/session.php';
include_once __DIR__ . "/funcs.php";
require_once __DIR__ . '/inc/access.php';
require_once __DIR__ . '/inc/overtime.php';
save_last_location("time_add.php");
require_page_work_overtime_access();
include __DIR__ . "/php_tori/connect.php";

require_once __DIR__ . '/inc/work_overtime_controller.php';
handle_work_overtime_request($link);

require __DIR__ . '/views/work_overtime_page.php';
