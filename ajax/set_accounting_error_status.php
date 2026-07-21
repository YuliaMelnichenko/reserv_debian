<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();

ajax_text_headers();

$supervisorID = (int)($_SESSION['ss_id'] ?? 0);
$errorID = (int)($_POST['error_id'] ?? 0);
$status = (int)($_POST['status'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));

if (!in_array($status, array(2, 3, 4), true)) {
    deny_ajax_access(400, 'Некорректный статус решения.');
}

if (strlen($comment) > 4000) {
    deny_ajax_access(400, 'Комментарий не должен превышать 4000 символов.');
}

require_ajax_accounting_error_supervisor($errorID, 3);

include __DIR__ . '/../php_tori/connect.php';

if (!mysqli_begin_transaction($link)) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
}

$result = db_query(
    $link,
    'SELECT STATUS FROM accounting_errors WHERE ID = ? LIMIT 1 FOR UPDATE',
    'i',
    array($errorID)
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

if ((int)$row['STATUS'] === 4 && $status !== 4) {
    mysqli_rollback($link);
    deny_ajax_access(409, 'Удаленную запись нельзя изменить.');
}

$updated = db_execute(
    $link,
    'UPDATE accounting_errors
     SET STATUS = ?, SUPERVISORID = ?, SUPERVISOR_COMMENT = ?, SUPERVISOR_REPLY_DT = NOW()
     WHERE ID = ?',
    'iisi',
    array($status, $supervisorID, $comment, $errorID)
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
