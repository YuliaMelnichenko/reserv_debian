<?php

require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . '/../funcs.php';
include __DIR__ . '/../php_tori/connect.php';

$userID = (int)$_SESSION['ss_id'];
$syncResult = sync_accounting_errors_for_user(
    $link,
    $userID,
    get_accounting_errors_default_depth_days()
);

if ($syncResult === false) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
}

$_SESSION['accounting_errors_sync_date'] = date('Y-m-d');
echo get_accounting_errors_count($link, $userID);
