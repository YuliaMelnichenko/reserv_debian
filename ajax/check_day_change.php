<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

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