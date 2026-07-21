<?php

require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$itemId = request_post_int('itemId');
require_ajax_add_time_access($itemId);

include_once __DIR__ . "/../php_tori/connect.php";

$query = db_execute($link, 'DELETE FROM ADD_TIME WHERE ID = ?', 'i', array($itemId));
$merr = mysqli_error($link);

if (!$query)
{
  echo database_error_message($link, __FILE__ . ':' . __LINE__);
}
else
{
  echo "1";
}
?>
