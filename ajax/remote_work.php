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

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supervisor_id'])) {
//     $supervisor_id = intval($_POST['supervisor_id']);

//     $stmt = mysqli_prepare($link, "INSERT INTO remote_work (user_id, supervisor_id, date_approval) VALUES (?, ?, NOW())");
//     mysqli_stmt_bind_param($stmt, "ii", $userID, $supervisor_id);

//     if (mysqli_stmt_execute($stmt)) {
//         echo json_encode(["status" => "success", "message" => "Данные сохранены"]);
//     } else {
//         echo json_encode(["status" => "error", "message" => "Ошибка сохранения"]);
//     }
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supervisor_id'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if (!$userID) {
            throw new Exception('Нет userID в сессии');
        }

        if (!isset($_POST['supervisor_id'])) {
            throw new Exception('Не передан supervisor_id');
        }

        $supervisor_id = intval($_POST['supervisor_id']);

        $sql = "INSERT INTO remote_work (user_id, supervisor_id, date_approval) VALUES (?, ?, NOW())";
        $stmt = mysqli_prepare($link, $sql);

        if (!$stmt) {
            throw new Exception('Prepare failed: ' . mysqli_error($link));
        }

        if (!mysqli_stmt_bind_param($stmt, "ii", $userID, $supervisor_id)) {
            throw new Exception('Bind failed: ' . mysqli_error($link));
        }

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Execute failed: ' . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_bind_param($stmt, "ii", $userID, $supervisor_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["status" => "success", "message" => "Данные сохранены"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Ошибка сохранения"]);
        }

        // echo json_encode(["status" => "success", "message" => "Данные сохранены"]);
        exit;
    } catch (Throwable $e) {
        error_log("remote_work.php POST error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

try {
    if (!$userID) {
        throw new Exception('No userID in session');
    }
    $sql = "
        SELECT DISTINCT g.SUPERVISORID AS id, 
               CONCAT_WS(' ', e.lastname, e.firstname, e.surname) AS fio
        FROM GROUPS g
        JOIN employees e ON g.SUPERVISORID = e.id
        WHERE TRIM(g.TYPE) = '3' AND g.USERID = ?
        ORDER BY fio
    ";

    $stmt = mysqli_prepare($link, $sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($link));
    }

    mysqli_stmt_bind_param($stmt, "i", $userID);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $supervisors = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $supervisors[] = $row;
    }
} catch (Throwable $e) {
    error_log("remote_work.php GET error: " . $e->getMessage());
    echo "<div style='padding: 10px; color:#900;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}


// $result = mysqli_query($link, $sql);

// $supervisors = [];
// while ($row = mysqli_fetch_assoc($result)) {
//     $fio = trim($row['surname'] . " " . $row['firstname'] . " " . $row['lastname']);
//     $supervisors[] = ["id" => $row['id'], "fio" => $fio];
// }
?>

<div id = "modalWindow">
    <div id="head_container">
        <h5 class="big" style="text-align: left;">С кем согласовано: </h5>
        <img id="closeRemoteBtn" src="img/closeSmall.png" style="cursor:pointer" alt="Закрыть">
    </div>
    <div>
        <select id="supervisor">";
            <option value="">-- Выберите руководителя --</option>
            <?php foreach ($supervisors as $s): ?>
                <option value="<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($s['fio'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="margin-top: 15px;">
        <br><button id="saveRemoteBtn">Сохранить</button>
    </div>
</div>

<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" src="js/tory.js"></script> 

