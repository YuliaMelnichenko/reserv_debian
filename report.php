<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
require_page_auth();

require __DIR__ . '/views/report_page.php';
