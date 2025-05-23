<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
session_start();

////////////////////////////////////////////////////////
include_once "/var/www/tori/funcs.php";
include "/var/www/tori/php_tori/connect.php";
mysqli_set_charset($link, "utf8");
save_last_location( "time_add.php" );
auth();
////////////////////////////////////////////////////////

if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    header('Content-Type: application/json');

    $id = intval($_GET['id']);

    $stmt = mysqli_prepare($link, "SELECT id, start_date, stop_date, fio FROM staff_leaves WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка запроса']);
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['status' => 'success', 'record' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Запись не найдена']);
    }
    exit;
}

function getEmployees($link) {
    $employees = [];
    $res = mysqli_query($link, "SELECT id, firstname, surname FROM employees WHERE relevance = 1 ORDER BY surname");

    while ($row = mysqli_fetch_assoc($res)) {
        $employees[$row['id']] = $row['surname'] . ' ' . $row['firstname'];
    }
    return $employees;
}

if (isset($_GET['action']) && $_GET['action'] === 'load') {
    header('Content-Type: application/json');

    $type = $_GET['type'] ?? 'Отпуск';

    $stmt = mysqli_prepare($link, "SELECT id, fio, start_date, stop_date, event FROM staff_leaves WHERE event = ? ORDER BY start_date DESC");
    mysqli_stmt_bind_param($stmt, 's', $type);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $start = strtotime($row['start_date']);
        $stop = strtotime($row['stop_date']);
        $days = round(($stop - $start) / 86400) + 1;

        $rows[] = [
            'id' => $row['id'],
            'name' => $row['fio'],
            'start_date' => $row['start_date'],
            'stop_date' => $row['stop_date'],
            'event' => $row['event'],
            'total_days' => $days
        ];
    }
    echo json_encode($rows);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    header('Content-Type: application/json');

    try {
        $userId = intval($_POST['employee_id'] ?? 0);
        $start = $_POST['start_date'] ?? '';
        $stop = $_POST['stop_date'] ?? '';
        $event = $_POST['event'] ?? '';
    
        if (!$userId || !$start || !$stop || !$event) {
            throw new Exception('не все поля заполнены');
        }

        $stmt = mysqli_prepare($link, "SELECT surname, firstname FROM employees WHERE id = ? LIMIT 1");
        if(!$stmt) {
            throw new Exception('Ошибка подготовки запроса (FIO): ' . mysqli_error($link));
        }

        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        if(!$row = mysqli_fetch_assoc($result)) {
            throw new Exception('Сотрудник не найден');
        }

        $fio = $row['surname'] . ' ' . $row['firstname'];
        if (!$fio) {
            throw new Exception('ФИО не получено');
        }

        $u = $userId;
        $f = $fio;
        $s = $start;
        $e = $stop;
        $t = $event;

        $stmt = mysqli_prepare($link, "INSERT INTO staff_leaves (user_id, fio, start_date, stop_date, event) VALUES (?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param($stmt, 'issss', $u, $f, $s, $e, $t);
        mysqli_stmt_execute($stmt);

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        error_log('Ошибка добавления: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
    header('Content-Type: application/json');

    try {
        $id = intval($_POST['record_id'] ?? 0);
        $start = $_POST['start_date'] ?? '';
        $stop = $_POST['stop_date'] ?? '';

        if (!$id || !$start || !$stop) {
            throw new Exception('Поля заполнены некорректно');   
        }

        $stmt = mysqli_prepare($link, "UPDATE staff_leaves SET start_date = ?, stop_date = ? WHERE id = ? ");
        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса" . mysqli_error($link));
        }

        mysqli_stmt_bind_param($stmt, 'ssi', $start, $stop, $id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Ошибка выполнения запроса: " . mysqli_error($link));
        }

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        error_log('Ошибка редактирования: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="style/style.css">
<link rel="stylesheet" href="style/main.css">
</head>
<body bgcolor="#ffffff">

<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" src="js/tory.js"></script>

<?php
echo "<div align=\"left\">";
echo "<table border=0>";
echo "<tr>";
echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";

include_once "/var/www/tori/navigate.php";

echo "</td>";
   
$wholeWidth = 700;

echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

echo "<h5 class=\"dark\"><br>/Отпуска и больничные сотрудников <br></h5>";

echo "<div id=\"event_buttons\">";
    echo "<div id=\"events\">";
        echo "<button id=\"btn_vacations\" onclick=\"\">Отпуска</button><br>";
        echo "<button id=\"btn_sick\" onclick=\"\">Больничные</button><br>";
    echo "</div>";
    echo "<div id=\"add_info_block\">";
        echo "<button id=\"btn_add\" title=\"Добавить запись\">";
        echo "<img src=\"img/plus.png\" alt=\"Добавить запись\" height=\"24\">";
        echo "</button><br>";
    echo "</div>";
echo "</div>";
?>

<table id="leave_table">
    <thead>
        <tr>
            <th>Сотрудник</th>
            <th>Дата начала</th>
            <th>Дата конца</th>
            <th>Кол-во дней</th>
            <th>Событие</th>
            <th></th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<div id="toast"> ✅ Запись обновлена </div>

<div id="modal">
    <h4 id="modalTitle">Добавление записи</h4>
    <p id="employeeName"></p>
    <form id="addForm">
        <div id="modal_form_block">
            <input type="hidden" name="record_id" id="record_id">
            <div class="modal_labels" id="selectEmployeeBlock">
                <label style="font-family: Arial,sans; font-size: 13px; color: #333333; font-weight: 700; margin-bottom: 5px;">Сотрудник:</label>
                <select name="employee_id">
                    <option value="">Выберите...</option>
                    <?php foreach (getEmployees($link) as $id => $fio): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($fio) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal_labels">
                <label style="font-family: Arial,sans; font-size: 13px; color: #333333; font-weight: 700; margin-bottom: 5px;">Дата начала: </label>
                <input type="date" name="start_date" required>
            </div>
            <div class="modal_labels">
                <label style="font-family: Arial,sans; font-size: 13px; color: #333333; font-weight: 700; margin-bottom: 5px;">Дата конца:</label>
                <input type="date" name="stop_date" required>
            </div>
            <div class="modal_labels">
                <label style="font-family: Arial,sans; font-size: 13px; color: #333333; font-weight: 700; margin-bottom: 5px;">Событие:</label>
                <select style="width: 110px;" name="event" required>
                    <option value="">Выберите...</option>
                    <option value="Отпуск">Отпуск</option>
                    <option value="Больничный">Больничный</option>
                </select>
            </div>
        </div>
        <div id="modal_form_btn">
            <button type="submit" style="cursor: pointer; font-size: 100%; width:100px; height:25px; background-color:#f8d888; border:1px solid #888888;">Сохранить</button>
            <button type="button" style="cursor: pointer; font-size: 100%; width:100px; height:25px; background-color:#ff7979; border:1px solid #888888;" onclick="closeModal()">Отмена</button>
        </div>
    </form>
</div>

<script>
    let currentType = null;

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('btn_vacations').addEventListener('click', () => {
            currentType = 'Отпуск';
            loadLeaves(currentType);
        });
        document.getElementById('btn_sick').addEventListener('click', () => {
            currentType = 'Больничный';
            loadLeaves(currentType);
        }); 
        document.getElementById('btn_add').addEventListener('click', () => {
            openModal('add');
        });

        document.getElementById('addForm').addEventListener('submit', (e) => {
            e.preventDefault();

            const saveBtn = e.target.querySelector('button[type="submit"]');
            saveBtn.disabled = true;

            const formData = new FormData(e.target);
            const isEdit = document.getElementById('record_id').value !== '';
            formData.append('action', isEdit ? 'update' : 'add');

            fetch('staff_leaves.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                try {
                    console.log('Сырой ответ от сервера: ', text);
                    const data =JSON.parse(text);

                    if (data.status === 'success') {
                        closeModal();
                        showToast("✅ Запись успешно обновлена");
                        // if (currentType) loadLeaves(currentType);
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                } catch (err) {
                    console.error('Ошибка парсинга JSON: ', err);
                    console.warn('Ответ сервера: ', text);
                    alert('Ошибка парсинга ответа сервераю Проверь консоль.');
                }
            })
            .catch(err => {
                console.error('Ошибка добавления:', err);
            })
            .finally(() => {
                saveBtn.disabled = false;
            });
        });
    });

    function loadLeaves (type) {
        fetch('staff_leaves.php?action=load&type=' + encodeURIComponent(type))
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    const table = document.getElementById('leave_table');
                    const tbody = table.querySelector('tbody');
                    tbody.innerHTML = "";

                    data.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${row.name}</td>
                            <td>${row.start_date}</td>
                            <td>${row.stop_date}</td>
                            <td>${row.total_days}</td>
                            <td>${row.event}</td>
                            <td>
                                <button id="btn_red" onclick='editLeave(${row.id})' title="Редактировать">
                                    <img src="img/red2.png" alt="Редактировать" width="20" height="20">
                                </button>
                            </td>
                        `
                        tbody.appendChild(tr);
                    });
                    table.style.display = 'table';
                } catch (err) {
                    console.error('Ошибка JSON: ', err);
                    console.warn('Ответ сервера: ', text);
                }
            });
    }

    function editLeave (id) {
        fetch(`staff_leaves.php?action=get&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    openModal('edit', data.record);
                } else {
                    alert('ошибка загрузки');
                }
            });
    }

    function openModal(mode, record = null) {
        const modal = document.getElementById('modal');
        const title = document.getElementById('modalTitle');
        const nameInfo = document.getElementById('employeeName');
        const recordIdInput = document.getElementById('record_id');
        const employeeSelect = document.querySelector('[name="employee_id"]');
        const employeeBlock = document.getElementById('selectEmployeeBlock');

        document.getElementById('addForm').reset();
        recordIdInput.value = '';
        nameInfo.textContent = '';

        if (mode === 'add') {
            title.textContent = 'Добавление записи';

            employeeBlock.style.display = 'flex';
            modal.style.width = '650px'
            modal.style.height = '120px'

            employeeSelect.required = true;
        } else if (mode === 'edit' && record) {
            title.textContent = 'Внесите корректировки';
            recordIdInput.value = record.id;

            modal.style.width = '460px'
            modal.style.height = '140px'

            employeeSelect.required = false;

            document.querySelector('[name="start_date"]').value = record.start_date;
            document.querySelector('[name="stop_date"]').value = record.stop_date;

            nameInfo.textContent = 'Сотрудник: ' + record.fio;

            employeeBlock.style.display = 'none';
        }
        modal.style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('modal').style.display = 'none';
        document.getElementById('addForm').reset();
    }

    function showToast (message = "✅ успешно", delay = 1500) {
        const toast = document.getElementById('toast');

        toast.textContent = message;
        
        toast.style.display = 'block';

        setTimeout(() => {
            toast.style.display = 'none';
            location.reload();
        }, delay);
    }

</script>

<?php
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script>

</body>
</html>

