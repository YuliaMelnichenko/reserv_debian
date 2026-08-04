<?php
date_default_timezone_set("Asia/Novosibirsk");
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/report_periods.php';
include_once __DIR__ . "/start.php";
if (
    !isset($_SESSION['rep_start_date']) ||
    !isset($_SESSION['rep_stop_date'])
) {
    $currDate = date('Y-m-d');

    list($start, $end) = getQuarterDates($currDate);

    $_SESSION['rep_start_date'] = $start;
    $_SESSION['rep_stop_date']  = $end;
    $_SESSION['rep_start_stop_date_mode'] = 4;
    $_SESSION['rep_start_stop_date_set'] = 1;
}

require __DIR__ . '/views/my_report_page.php';
