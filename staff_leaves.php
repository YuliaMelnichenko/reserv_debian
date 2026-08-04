<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
include_once __DIR__ . "/funcs.php";
require_once __DIR__ . '/inc/access.php';
save_last_location("time_add.php");
require_page_staff_leaves_access();
include __DIR__ . "/php_tori/connect.php";

require_once __DIR__ . '/inc/staff_leaves.php';
require_once __DIR__ . '/inc/staff_leaves_export.php';
require_once __DIR__ . '/inc/staff_leaves_controller.php';

if (handleStaffLeavesRequest($link, $_SERVER, $_GET, $_POST)) {
    exit;
}

require __DIR__ . '/views/staff_leaves_page.php';
