<?php 

session_start();
header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['ss_id'])) {
    echo 0;
    exit;
}

echo isset($_SESSION['ss_state']) ? (int)$_SESSION['ss_state'] : 0;

?>