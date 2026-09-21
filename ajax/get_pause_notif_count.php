<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
require_once __DIR__ . "/../inc/notification_summary.php";
include __DIR__ . "/../php_tori/connect.php";

echo '<h5 class="biggersmall">По приостановкам учета времени</h5>';
?>
