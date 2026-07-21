<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();

ajax_text_headers();

$userID = (int)$_SESSION['ss_id'];
$errorID = request_post_int('error_id');
$comment = request_post_trimmed_string('comment');

if ($errorID <= 0) {
    deny_ajax_access(400, 'Некорректная запись ошибки учета.');
}

if ($comment === '' || strlen($comment) > 4000) {
    deny_ajax_access(400, 'Комментарий должен содержать от 1 до 4000 символов.');
}

include __DIR__ . '/../php_tori/connect.php';

$transaction = db_transaction_start($link);
if (!$transaction) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
}

$result = db_query(
    $link,
    'SELECT STATUS FROM accounting_errors WHERE ID = ? AND USERID = ? LIMIT 1 FOR UPDATE',
    'ii',
    array($errorID, $userID)
);

if (!$result) {
    $transaction->rollback();
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
}

$row = mysqli_fetch_assoc($result);

if (!$row) {
    $transaction->rollback();
    deny_ajax_access(404, 'Запись ошибки учета не найдена.');
}

$status = (int)$row['STATUS'];

if (in_array($status, array(2, 4), true)) {
    $transaction->rollback();
    deny_ajax_access(409, 'Комментарий нельзя изменить после принятия или удаления записи.');
}

$updated = db_execute(
    $link,
    'UPDATE accounting_errors
     SET USER_COMMENT = ?, STATUS = 1, USER_REPLY_DT = NOW()
     WHERE ID = ? AND USERID = ? AND STATUS IN (0, 1, 3)',
    'sii',
    array($comment, $errorID, $userID)
);

if (!$updated) {
    $transaction->rollback();
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
}

if (!$transaction->commit()) {
    ajax_database_error($link, __FILE__ . ':' . __LINE__);
    exit;
}

echo '1';
