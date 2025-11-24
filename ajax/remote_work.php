<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

header("Content-type: text/html; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$userID = $_SESSION['ss_id'] ?? null;

include_once __DIR__ . "/../funcs.php";
include __DIR__ . "/../php_tori/connect.php";
mysqli_set_charset($link, "utf8");

// -------------------- POST (SAVE) -------------------- //

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supervisor_id'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if (!$userID) {
            echo json_encode([
                "status" => "error",
                "message" => "Нет userID в сессии"
            ]);
            exit;        
        }

        $supervisor_id = intval($_POST['supervisor_id']);

        if ($supervisor_id <= 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Некорректный supervisor_id"
            ]);
            exit;          
        }

        $checkSql = "SELECT id
                    FROM remote_work
                    WHERE user_id = ? AND DATE(date_approval) = CURDATE()
                    LIMIT 1";
        
        $checkStmt = mysqli_prepare($link, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "i", $userID);
        mysqli_stmt_execute($checkStmt);

        $checkRes = mysqli_stmt_get_result($checkStmt);
        if (mysqli_num_rows($checkRes) > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Вы уже создали запись на сегодня"
            ]);
            exit;
        }

        $sql = "INSERT INTO remote_work (user_id, supervisor_id, date_approval) VALUES (?, ?, NOW())";

        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $userID, $supervisor_id);

        if (!mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "status" => "error",
                "message" => "Ошибка сохранения: " . mysqli_stmt_error($stmt)
            ]);
            exit;
        }

        echo json_encode(["status" => "success"]);
        exit;

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

// -------------------- GET (FORM) -------------------- //

try {
    if (!$userID) {
        throw new Exception('No userID in session');
    }

    $sql = "
        SELECT DISTINCT g.SUPERVISORID AS id, 
               CONCAT_WS(' ', e.surname, e.firstname, e.lastname) AS fio
        FROM GROUPS g
        JOIN employees e ON g.SUPERVISORID = e.id
        WHERE TRIM(g.TYPE) = '3' AND g.USERID = ?
        ORDER BY fio
    ";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userID);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $supervisors = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $supervisors[] = $row;
    }

} catch (Throwable $e) {
    echo "<div style='padding: 10px; color:#900;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<div id = "modalWindow">
    <div id="head_container">
        <h5 class="big" style="text-align: left;">С кем согласовано: </h5>
        <img id="closeRemoteBtn" src="img/closeSmall.png" style="cursor:pointer; width: 14px; height: 14px;" alt="Закрыть">
    </div>
    <div>
        <select id="supervisor">";
            <option value="">-- Выберите руководителя --</option>
            <?php foreach ($supervisors as $s): ?>
                <option value="<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($s['fio'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <input type="hidden" id="employeeId" value="<?= htmlspecialchars($userID, ENT_QUOTES, 'UTF-8') ?>">
    <div style="margin: 15px 0;">
        <button id="saveRemoteBtn">Сохранить</button>
    </div>
</div>

