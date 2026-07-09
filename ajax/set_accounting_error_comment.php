<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$userID = (int)$_SESSION['ss_id'];
$errorID = (int)($_POST['error_id'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));

if ($errorID <= 0) {
    deny_ajax_access(400, 'Некорректная запись ошибки учета.');
}

if ($comment === '' || strlen($comment) > 4000) {
    deny_ajax_access(400, 'Комментарий должен содержать от 1 до 4000 символов.');
}

include __DIR__ . '/../php_tori/connect.php';

if (!mysqli_begin_transaction($link)) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
}

$result = db_query(
    $link,
    'SELECT STATUS FROM accounting_errors WHERE ID = ? AND USERID = ? LIMIT 1 FOR UPDATE',
    'ii',
    array($errorID, $userID)
);

if (!$result) {
    mysqli_rollback($link);
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
}

$row = mysqli_fetch_assoc($result);

if (!$row) {
    mysqli_rollback($link);
    deny_ajax_access(404, 'Запись ошибки учета не найдена.');
}

$status = (int)$row['STATUS'];

if (in_array($status, array(2, 4), true)) {
    mysqli_rollback($link);
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
    mysqli_rollback($link);
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
}

if (!mysqli_commit($link)) {
    mysqli_rollback($link);
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
}

echo '1';
