<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
$_SESSION['ss_dayWasChanged'] = 1;
?>