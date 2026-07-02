<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
$_SESSION['ss_dayWasChanged'] = 1;
?>