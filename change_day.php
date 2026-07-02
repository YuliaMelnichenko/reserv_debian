<?php
session_start();


require_once __DIR__ . '/inc/access.php';
require_page_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  exit;
}

$_SESSION['ss_dayWasChanged'] = 1;
?>
