<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$ss_dayWasChanged = isset($_SESSION['ss_dayWasChanged'])
  ? (int)$_SESSION['ss_dayWasChanged']
  : 0;

if ($ss_dayWasChanged == 1) {
  $_SESSION['ss_dayWasChanged'] = 0;
  echo "1";
}
else {
  echo "0";
}
?>