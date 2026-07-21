<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if (request_post_has('userID') && request_post_has('messageMode'))
{
  $userID = request_post_int('userID');
  $messageMode = request_post_int('messageMode');
  $superUserID = $_SESSION['ss_id']; 

  if ($userID <= 0) {
    deny_ajax_access(400, 'INVALID_USER');
  }

  if (!in_array($messageMode, array(1, 2), true)) {
    deny_ajax_access(400, 'INVALID_MODE');
  }

  require_ajax_supervisor_for_user($userID, 3);

  include_once __DIR__ . "/../funcs.php";
  include_once __DIR__ . "/../php_tori/connect.php";

  $currentDate = date('Y-m-d');
  
  if ( $messageMode == 1 ){ $messageModeStr = "Сведения о регистрации времени удалены. Решение принял: "; }
  if ( $messageMode == 2 ){ $messageModeStr = "Сведения о приходе на рабочее место изменены. Решение принял: "; }

  $messageModeStr = $messageModeStr.get_user_name_by_id( $superUserID );

  $transaction = db_transaction_start($link);
  if (!$transaction) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  $query = db_query($link, 'SELECT ID FROM ALERTS ORDER BY ID DESC LIMIT 1 FOR UPDATE');

  if (!$query) {
    $transaction->rollback();
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  $lastAlert = mysqli_fetch_assoc($query);
  $newID = $lastAlert ? (int)$lastAlert['ID'] + 1 : 1;

  $query = db_execute(
    $link,
    'INSERT INTO ALERTS VALUES (?, ?, ?, ?, ?, 0)',
    'isiis',
    array($newID, $currentDate, $userID, (int)$superUserID, $messageModeStr)
  );

  if (!$query) {
    $transaction->rollback();
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  if (!$transaction->commit()) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  echo "1";
  exit;
}

echo "0";

?>
