<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$userID = (int)$_SESSION['ss_id'];

include_once __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";

$query = time_journal_query_open_pause($link, $userID);

if (!$query) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

echo db_has_rows($query) ? "1" : "0";
?>
