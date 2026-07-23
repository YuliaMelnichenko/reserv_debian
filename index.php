<?php

date_default_timezone_set("Asia/Novosibirsk");
ob_start();
require_once __DIR__ . '/inc/session.php';
include_once __DIR__ . "/start.php";
include_once __DIR__ . "/funcs.php";
require_once __DIR__ . '/inc/index_page_service.php';
auth();

require __DIR__ . '/views/index_page.php';
