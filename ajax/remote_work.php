<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_response_headers('text/html');

$userID = $_SESSION['ss_id'] ?? null;

include_once __DIR__ . "/../funcs.php";
include __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . "/../inc/remote_work.php";
mysqli_set_charset($link, "utf8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ajax_json_headers();

    if (!$userID) {
        ajax_json_response(array('status' => 'error', 'message' => 'Нет userID в сессии'));
        exit;
    }

    $userID = (int)$userID;

    if (request_post_string('action') === 'finish') {
        $result = finish_remote_work($link, $userID);

        if ($result === false) {
            ajax_json_application_error('Remote work finish at ' . __FILE__ . ':' . __LINE__, mysqli_error($link));
            exit;
        }

        ajax_json_response($result);
        exit;
    }

    if (request_post_has('supervisor_id')) {
        $result = start_remote_work($link, $userID, request_post_int('supervisor_id'));

        if ($result === false) {
            ajax_json_application_error('Remote work creation at ' . __FILE__ . ':' . __LINE__, mysqli_error($link));
            exit;
        }

        $statusCode = $result['status'] === 'forbidden' ? 403 : null;

        if ($result['status'] === 'forbidden') {
            $result['status'] = 'error';
        }

        ajax_json_response($result, $statusCode);
        exit;
    }

    ajax_json_response(array('status' => 'error', 'message' => 'Неверные данные POST'));
    exit;
}

$userID = (int)$userID;
$supervisors = $userID > 0 ? get_remote_work_supervisors($link, $userID) : false;
$openRow = $userID > 0 ? get_open_remote_work($link, $userID) : false;

if ($supervisors === false || $openRow === false) {
    $details = $userID > 0 ? mysqli_error($link) : 'No userID in session';
    $message = application_error_message('Remote work form at ' . __FILE__ . ':' . __LINE__, $details);
    echo "<div style='padding: 10px; color:#900;'>" . html_escape($message) . "</div>";
    exit;
}
?>

<div id="modalWindow">
    <div id="head_container">
        <?php if ($openRow): ?>
            <h5 class="big" style="text-align:left;">Завершить удалённую работу</h5>
        <?php else: ?>
            <h5 class="big" style="text-align:left;">С кем согласовано:</h5>
        <?php endif; ?>
        <img id="closeRemoteBtn" src="img/closeSmall.png" style="cursor:pointer; width: 14px; height: 14px;" alt="Закрыть">
    </div>

    <?php if ($openRow): ?>
        <!-- Форма завершения удалёнки -->
        <div id="finishRemoteContainer" class="remote-finish-block" style="margin-top:10px;">
            <p>Вы в настоящий момент на удалённой работе.</p>
            <p><strong>Руководитель:</strong> <?= htmlspecialchars($openRow['supervisor_fio'] ?: '—', ENT_QUOTES, 'UTF-8') ?></p>

            <input type="hidden" id="remoteId" value="<?= htmlspecialchars($openRow['id'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="employeeId" value="<?= htmlspecialchars($userID, ENT_QUOTES, 'UTF-8') ?>">

            <div style="margin: 15px 0;">
                <button id="finishRemoteBtn">Завершить</button>
            </div>
        </div>

    <?php else: ?>
        <!-- Форма начала удалёнки -->
        <div id="startRemoteContainer" class="remote-start-block">
            <select id="supervisor">
                <option value="">-- Выберите руководителя --</option>
                <?php foreach ($supervisors as $s): ?>
                    <option value="<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($s['fio'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <input type="hidden" id="employeeId" value="<?= htmlspecialchars($userID, ENT_QUOTES, 'UTF-8') ?>">

        <div style="margin: 15px 0;">
            <button id="saveRemoteBtn">Сохранить</button>
        </div>

    <?php endif; ?>
</div>

