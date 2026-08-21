<?php

require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    deny_ajax_access(405, 'METHOD_NOT_ALLOWED');
}

require_once __DIR__ . '/../inc/add_time_journal_period.php';

$mode = request_post_int('period_mode');
$startDate = request_post_date('start_date');
$stopDate = request_post_date('stop_date');
$period = get_add_time_journal_period($mode, $startDate, $stopDate, date('Y-m-d'));

if ($period === null) {
    ajax_json_response(array(
        'status' => 'error',
        'message' => 'Укажите корректный период продолжительностью не более одного года.'
    ), 400);
    exit;
}

$_SESSION['add_time_journal_period_mode'] = $period['mode'];
$_SESSION['add_time_journal_period_start_date'] = $period['start_date'];
$_SESSION['add_time_journal_period_stop_date'] = $period['stop_date'];

ajax_json_response(array('status' => 'ok', 'period' => $period));
