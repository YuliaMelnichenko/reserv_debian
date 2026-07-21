<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if (request_post_has('userID'))
{
  $userID = request_post_int('userID');
  if ($userID <= 0) {
    deny_ajax_access(400, 'INVALID_USER');
  }

  require_ajax_supervisor_for_user($userID, 3);

  include_once __DIR__ . "/../funcs.php";
  include_once __DIR__ . "/../php_tori/connect.php";

  $currentDateTime = get_current_datetime_in_timezone()[1];
  $dateRange = datetimestr_to_day_start_stop_DT_ex_str($currentDateTime, '00:00:00');

  $query = db_execute(
    $link,
    'DELETE FROM visiting WHERE user_id = ? AND in_dt >= ? AND in_dt <= ?',
    'iss',
    array($userID, $dateRange[0], $dateRange[1])
  );

  if (!$query) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
  }  

  echo "1";
  exit;
}
echo "0";
?>
