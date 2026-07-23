<?php

if (empty($_SESSION['rep_start_date']) || empty($_SESSION['rep_stop_date'])) {
    die('Ошибка: не задан период отчета');
}

if (strtotime($_SESSION['rep_start_date']) > strtotime($_SESSION['rep_stop_date'])) {
    die('Ошибка: некорректный диапазон дат');
}

require_once __DIR__ . '/funcs.php';
require_once __DIR__ . '/inc/report_statistics.php';
require_once __DIR__ . '/inc/report_renderer.php';
