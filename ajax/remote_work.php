<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_response_headers('text/html');

$userID = $_SESSION['ss_id'] ?? null;

include_once __DIR__ . "/../funcs.php";
include __DIR__ . "/../php_tori/connect.php";
mysqli_set_charset($link, "utf8");

// -------------------- POST (SAVE) -------------------- //

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ajax_json_headers();

    try {
        if (!$userID) {
            ajax_json_response(["status" => "error", "message" => "Нет userID в сессии"]);
            exit;        
        }

        // Завершение удалёнки
        if (isset($_POST['action']) && $_POST['action'] === 'finish') {
            // Найдём открытую запись (stop_dt IS NULL) для этого пользователя за сегодня
            $findSql = "SELECT id FROM remote_work WHERE user_id = ? AND DATE(start_dt) = CURDATE() AND stop_dt IS NULL ORDER BY id DESC LIMIT 1";
            $findStmt = mysqli_prepare($link, $findSql);
            if (!$findStmt) {
                throw new RuntimeException('Ошибка подготовки поиска удаленной работы: ' . mysqli_error($link));
            }

            mysqli_stmt_bind_param($findStmt, "i", $userID);
            if (!mysqli_stmt_execute($findStmt)) {
                throw new RuntimeException('Ошибка поиска удаленной работы: ' . mysqli_stmt_error($findStmt));
            }

            $findRes = mysqli_stmt_get_result($findStmt);
            $row = mysqli_fetch_assoc($findRes);

            if (!$row) {
                ajax_json_response(["status" => "error", "message" => "Запись удалённой работы для завершения не найдена"]);
                exit;
            }
            
            $remoteId = intval($row['id']);
            $updSql = "UPDATE remote_work SET stop_dt = NOW() WHERE id = ? LIMIT 1";
            $updStmt = mysqli_prepare($link, $updSql);
            if (!$updStmt) {
                throw new RuntimeException('Ошибка подготовки завершения удаленной работы: ' . mysqli_error($link));
            }

            mysqli_stmt_bind_param($updStmt, "i", $remoteId);

            if (!mysqli_stmt_execute($updStmt)) {
                ajax_json_application_error('Remote work finish at ' . __FILE__ . ':' . __LINE__, mysqli_stmt_error($updStmt));
                exit;
            }

            ajax_json_response(["status" => "success"]);
            exit;
        }

        // Создание новой записи (начало удалёнки)
        if (isset($_POST['supervisor_id'])) {
            $supervisor_id = intval($_POST['supervisor_id']);

            if ($supervisor_id <= 0) {
                ajax_json_response(["status" => "error", "message" => "Некорректный supervisor_id"]);
                exit;
            }

            $supervisorSql = "SELECT 1 FROM GROUPS WHERE USERID = ? AND SUPERVISORID = ? AND TRIM(TYPE) = '3' LIMIT 1";
            $supervisorStmt = mysqli_prepare($link, $supervisorSql);
            if (!$supervisorStmt) {
                throw new RuntimeException('Ошибка подготовки проверки руководителя: ' . mysqli_error($link));
            }

            mysqli_stmt_bind_param($supervisorStmt, "ii", $userID, $supervisor_id);
            if (!mysqli_stmt_execute($supervisorStmt)) {
                throw new RuntimeException('Ошибка проверки руководителя: ' . mysqli_stmt_error($supervisorStmt));
            }

            mysqli_stmt_store_result($supervisorStmt);
            if (mysqli_stmt_num_rows($supervisorStmt) === 0) {
                http_response_code(403);
                ajax_json_response(["status" => "error", "message" => "Выбранный руководитель недоступен"]);
                exit;
            }

            // Проверяем, что запись на сегодня ещё не создана (и нет незакрытой)
            $checkSql = "SELECT id FROM remote_work WHERE user_id = ? AND DATE(start_dt) = CURDATE() AND stop_dt IS NULL LIMIT 1";
            $checkStmt = mysqli_prepare($link, $checkSql);
            if (!$checkStmt) {
                throw new RuntimeException('Ошибка подготовки проверки удаленной работы: ' . mysqli_error($link));
            }

            mysqli_stmt_bind_param($checkStmt, "i", $userID);
            if (!mysqli_stmt_execute($checkStmt)) {
                throw new RuntimeException('Ошибка проверки удаленной работы: ' . mysqli_stmt_error($checkStmt));
            }

            $checkRes = mysqli_stmt_get_result($checkStmt);

            if (mysqli_num_rows($checkRes) > 0) {
                ajax_json_response(["status" => "error", "message" => "Вы уже начали удалённую работу сегодня"]);
                exit;
            }

            // Вставляем запись: start_dt = NOW(), stop_dt NULL
            $sql = "INSERT INTO remote_work (user_id, supervisor_id, start_dt) VALUES (?, ?, NOW())";
            $stmt = mysqli_prepare($link, $sql);
            if (!$stmt) {
                throw new RuntimeException('Ошибка подготовки удаленной работы: ' . mysqli_error($link));
            }

            mysqli_stmt_bind_param($stmt, "ii", $userID, $supervisor_id);

            if (!mysqli_stmt_execute($stmt)) {
                ajax_json_application_error('Remote work creation at ' . __FILE__ . ':' . __LINE__, mysqli_stmt_error($stmt));
                exit;
            }

            ajax_json_response(["status" => "success"]);
            exit;
        }

        // Если POST, но без нужных полей
        ajax_json_response(["status" => "error", "message" => "Неверные данные POST"]);
        exit;

    } catch (Throwable $e) {
        ajax_json_application_error('Remote work request at ' . __FILE__ . ':' . __LINE__, $e->getMessage());
        exit;
    }
}

// -------------------- GET (FORM) -------------------- //

// Если GET, вернём HTML-модалку: либо форму "Начать удалёнку" (select руководителя),
// либо форму "Завершить удалёнку" (когда уже есть открытая запись)

try {
    if (!$userID) {
        throw new Exception('No userID in session');
    }

    // Получаем список руководителей (как раньше)
    $sql = "
        SELECT DISTINCT g.SUPERVISORID AS id, 
               CONCAT_WS(' ', e.surname, e.firstname, e.lastname) AS fio
        FROM GROUPS g
        JOIN employees e ON g.SUPERVISORID = e.id
        WHERE TRIM(g.TYPE) = '3' AND g.USERID = ?
        ORDER BY fio
    ";

    $stmt = mysqli_prepare($link, $sql);
    if (!$stmt) {
        throw new RuntimeException('Ошибка подготовки списка руководителей: ' . mysqli_error($link));
    }

    mysqli_stmt_bind_param($stmt, "i", $userID);
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('Ошибка получения списка руководителей: ' . mysqli_stmt_error($stmt));
    }

    $res = mysqli_stmt_get_result($stmt);

    $supervisors = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $supervisors[] = $row;
    }

    // Проверим есть ли открытая запись remote_work для этого user (start_dt сегодня, stop_dt IS NULL)
    $checkOpenSql = "SELECT rw.id, rw.supervisor_id, CONCAT_WS(' ', e.surname, e.firstname, e.lastname) AS supervisor_fio
                     FROM remote_work rw
                     LEFT JOIN employees e ON rw.supervisor_id = e.id
                     WHERE rw.user_id = ? AND DATE(rw.start_dt) = CURDATE() AND rw.stop_dt IS NULL
                     ORDER BY rw.id DESC LIMIT 1";
    $chStmt = mysqli_prepare($link, $checkOpenSql);
    if (!$chStmt) {
        throw new RuntimeException('Ошибка подготовки проверки удаленной работы: ' . mysqli_error($link));
    }

    mysqli_stmt_bind_param($chStmt, "i", $userID);
    if (!mysqli_stmt_execute($chStmt)) {
        throw new RuntimeException('Ошибка проверки удаленной работы: ' . mysqli_stmt_error($chStmt));
    }

    $chRes = mysqli_stmt_get_result($chStmt);
    $openRow = mysqli_fetch_assoc($chRes);

} catch (Throwable $e) {
    echo "<div style='padding: 10px; color:#900;'>" . html_escape(application_error_message(__FILE__ . ':' . __LINE__, $e->getMessage())) . "</div>";
    exit;
}

// ---------- РЕНДЕР МОДАЛКИ (HTML) ---------- //
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

