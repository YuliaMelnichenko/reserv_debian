<?php 
ob_start();
require_once __DIR__ . '/inc/session.php';
////////////////////////////////////////////////////////
include_once __DIR__ . "/funcs.php";
include __DIR__ . "/php_tori/connect.php";
mysqli_set_charset($link, "utf8");
save_last_location( "time_add.php" );
auth();
////////////////////////////////////////////////////////

function getPeriodBounds (string $period): array {
    $today = date('Y-m-d 23:59:59');

    switch ($period) {
        case 'week':
            $start = date('Y-m-d 00:00:00', strtotime('monday this week'));
            // $end = $today;
            break;
        case 'month':
            $start = date('Y-m-01 00:00:00');
            // $end = $today;
            break;
        case 'quarter':
        default:
            $year = intval(date('Y'));
            $month = intval(date('n'));
            $quarter = intval(ceil($month / 3));
            $start_month = ($quarter - 1) * 3 + 1;
            $start = date("$year-$start_month-01 00:00:00");
            break;
    }
    return [$start, $today];
}

function formatHours($hours) {
    $minutes = intval(round($hours * 60));

    if ($minutes <= 0) return '—';

    $h = intdiv($minutes, 60);
    $m = $minutes % 60;

    if ($h > 0 && $m > 0) return "$h ч $m мин";

    if ($h > 0) return "$h ч";
    return "$m мин";
}

// === AJAX: список сотрудников с количеством переработок >= hours (текущий квартал) ===
if (isset($_GET['action']) && $_GET['action'] === 'load') {
    header('Content-Type: application/json; charset=utf-8');

    error_reporting(E_ALL);

    try {
        $hours = isset($_GET['hours']) ? floatval($_GET['hours']) : 9.0;
        $period = $_GET['period'] ?? 'quarter';

        if ($period === 'custom') {
            $start = $_GET['start'] ?? '';
            $end = $_GET['end'] ?? '';

            if (!$start || !$end) {
                throw new Exception('Не заданы даты для ручного ввода');
            }

            $qstart = date('Y-m-d 00:00:00', strtotime($start));
            $qend = date('Y-m-d 23:59:59', strtotime($end));
        } else {
            list($qstart, $qend) = getPeriodBounds($period);
        }

        $sql = "
            SELECT 
                e.id AS emp_id,
                CONCAT_WS(' ', e.surname, e.firstname, e.lastname) AS fio,
                COUNT(t.work_date) AS overtime_days,
                ROUND(SUM(t.total_hours) - (? * COUNT(t.work_date)), 2) AS overtime_hours
            FROM employees e
            LEFT JOIN (
                SELECT d.user_id, d.work_date,
                       (IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0)) AS total_hours
                FROM (
                    SELECT user_id, DATE(in_dt) AS work_date
                    FROM visiting
                    WHERE in_dt >= ? AND in_dt < ?
                    GROUP BY user_id, DATE(in_dt)
                    
                    UNION
                    
                    SELECT USERID AS user_id, DATE(START_DT) AS work_date
                    FROM ADD_TIME
                    WHERE START_DT >= ? AND START_DT < ? 
                      AND REASON IN (1, 2, 3, 4, 5)
                    GROUP BY USERID, DATE(START_DT)
                ) d 
                LEFT JOIN (
                    SELECT user_id, DATE(in_dt) AS work_date,
                        ROUND(SUM(
                            TIME_TO_SEC(TIMEDIFF(out_dt, in_dt))
                            - IF(eat_start_dt IS NULL OR eat_stop_dt IS NULL, 0, 
                                TIME_TO_SEC(TIMEDIFF(eat_stop_dt, eat_start_dt)))
                        ) / 3600, 2) AS office_hours
                    FROM visiting 
                    WHERE in_dt >= ? AND in_dt < ?
                      AND in_dt IS NOT NULL AND out_dt IS NOT NULL
                    GROUP BY user_id, DATE(in_dt)
                ) v ON d.user_id = v.user_id AND d.work_date = v.work_date
                LEFT JOIN (
                    SELECT USERID AS user_id, DATE(START_DT) AS work_date,
                        ROUND(SUM(TIME_TO_SEC(TIMEDIFF(STOP_DT, START_DT))) / 3600, 2) AS outside_hours
                    FROM ADD_TIME
                    WHERE START_DT >= ? AND START_DT < ?
                      AND REASON IN (1, 2, 3, 4, 5)
                    GROUP BY USERID, DATE(START_DT)
                ) a ON d.user_id = a.user_id AND d.work_date = a.work_date
                WHERE (IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0)) >= ?
            ) AS t ON e.id = t.user_id
            WHERE t.work_date IS NOT NULL
            GROUP BY e.id
            ORDER BY overtime_days DESC, fio ASC
        ";

        $stmt = mysqli_prepare($link, $sql);

        if (!$stmt) {
            throw new Exception('Ошибка подготовки запроса: ' . mysqli_error($link));
        }

        mysqli_stmt_bind_param($stmt, 
                                   'dssssssssd', 
                                     $hours, // 1
                                    $qstart, $qend, // 2-3 visiting (union)
                                           $qstart, $qend, // 4-5 add_time (union)
                                           $qstart, $qend, // 6-7 visiting (join)
                                           $qstart, $qend, // 8-9 add_time (join)
                                           $hours          // 10 (порог в часах)
        );
        
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);

        $rows = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'id' => intval($row['emp_id']),
                'fio' => $row['fio'],
                'overtime_count' => intval($row['overtime_days']),
                'overtime_hours' => floatval($row['overtime_hours'])
            ];
        }

        echo json_encode(['status' => 'success', 'data' => $rows, 'quarter_start' => $qstart, 'quarter_end' => $qend]);
    } catch (Exception $e) {
        error_log('Ошибка загрузки списка переработок: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// === AJAX: детали по сотруднику — записи (дата + часы) с переработкой >= hours (текущий квартал) ===
if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $empId = intval($_GET['id']);
        if ($empId <= 0) throw new Exception('Некорректный ID сотрудника');

        $hours = isset($_GET['hours']) ? floatval($_GET['hours']) : 9.0;
        if ($hours <= 0) $hours = 9.0;

        $period = $_GET['period'] ?? 'quarter';

        if ($period === 'custom') {
            $start = $_GET['start'] ?? '';
            $end = $_GET['end'] ?? '';

            if (!$start || !$end) {
                throw new Exception('Не заданы даты для ручного ввода');
            }
            $qstart = date('Y-m-d 00:00:00', strtotime($start));
            $qend = date('Y-m-d 23:59:59', strtotime($end));
        } else {
            list($qstart, $qend) = getPeriodBounds($period);
        }

        $sql = "
            SELECT 
                   d.work_date,
                   ROUND(IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0), 2) AS total_hours,
                   ROUND(IFNULL(v.office_hours, 0), 2) AS office_hours,
                   ROUND(IFNULL(a.outside_hours, 0), 2) AS outside_hours
            FROM (
                SELECT DATE(in_dt) AS work_date
                FROM visiting
                WHERE user_id = ?
                  AND in_dt >= ? AND in_dt < ?
                GROUP BY DATE(in_dt)

                UNION
                
                SELECT DATE(START_DT) AS work_date
                FROM ADD_TIME
                WHERE USERID = ? 
                  AND START_DT >= ? AND START_DT < ?
                  AND REASON IN (1, 2, 3, 4, 5)
                GROUP BY DATE(START_DT)
            ) d 
            LEFT JOIN (
                SELECT DATE(in_dt) AS work_date,
                       ROUND(SUM(
                         TIME_TO_SEC(TIMEDIFF(out_dt, in_dt))
                          - IF(eat_start_dt IS NULL OR eat_stop_dt IS NULL, 0,
                               TIME_TO_SEC(TIMEDIFF(eat_stop_dt, eat_start_dt)))
                         ) / 3600, 2) AS office_hours
                FROM visiting 
                WHERE user_id = ? 
                  AND in_dt >= ? AND in_dt < ?
                  AND in_dt IS NOT NULL AND out_dt IS NOT NULL
                GROUP BY DATE(in_dt) 
            ) v ON d.work_date = v.work_date
            LEFT JOIN (
                SELECT DATE(START_DT) AS work_date,
                       ROUND(SUM(TIME_TO_SEC(TIMEDIFF(STOP_DT, START_DT))) / 3600, 2) AS outside_hours
                FROM ADD_TIME
                WHERE USERID = ? 
                  AND START_DT >= ? AND START_DT < ?
                  AND REASON IN (1, 2, 3, 4, 5)
                GROUP BY DATE(START_DT)
            ) a ON d.work_date = a.work_date
            WHERE (IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0)) >= ?
            ORDER BY d.work_date DESC
        ";

        $stmt = mysqli_prepare($link, $sql);
        if (!$stmt) throw new Exception('Ошибка подготовки запроса ' . mysqli_error($link));

        if (mysqli_stmt_param_count($stmt) !== 13) {
            throw new Exception('Количество плейсхолдеров не совпадает: ' . mysqli_stmt_param_count($stmt));
        }

        mysqli_stmt_bind_param($stmt, 
                                   'ississississd', 
                                     $empId, $qstart, $qend, //visiting (union)
                                           $empId, $qstart, $qend, //add_time (union)
                                           $empId, $qstart, $qend, //visiting (join)
                                           $empId, $qstart, $qend, //add_time (join)
                                           $hours                  // порог в часах
        );

        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'date' => $row['work_date'],
                'hours_total' => formatHours($row['total_hours']),
                'office_hours' => formatHours($row['office_hours']),
                'outside_hours' => formatHours($row['outside_hours'])
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $rows,
            'quarter_start' => $qstart,
            'quarter_end' => $qend
        ]);
    } catch (Exception $e) {
        error_log('Ошибка деталей переработок: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

?>

<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <title>Выгрузка переработок — текущий квартал</title>
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/main.css">
    <style>
        .search-block { margin: 10px 0; display:flex; gap:8px; align-items:center; }
        .search-block input[type=number]{ width:100px; padding:4px; }
        #results_table { width:100%; border-collapse: collapse; }
        #results_table th, #results_table td { border:1px solid #ccc; padding:6px; }
        #modal_details { display:none; position:fixed; left:50%; top:50%; transform:translate(-50%,-50%); background:#fff; border:1px solid #888; padding:12px; z-index:1000; width:400px; max-height:70vh; overflow:auto; box-shadow:0 4px 12px rgba(0,0,0,0.2); }
        #modal_overlay { display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:900; }
        .btn { cursor:pointer; padding:3px 6px; border:1px solid #888; background:#f0f0f0; }
        .btn-primary { background:#f8d888; }
        .btn-danger { background:#ff7979; color:#fff; }
    </style>
</head>
<body>

<?php 
echo "<div align=\"left\">";
echo "<table border=0>";
echo "<tr>";
echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";
include_once __DIR__ . "/navigate.php";
echo "</td>";

$wholeWidth = 780;
echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width= $wholeWidth>";
echo "<h5 class=\"dark\"><br>/Выгрузка сотрудников по переработкам<br></h5>";
?>

<div class="search_block">
    <label for="hours_input" style="font-weight: 700;">Минимум часов (с учетом обеда)</label>
    <input type="number" id="hours_input" min="0" step="1" value="9">

    <label for="period_select" style="font-weight: 700; margin-left: 10px;">Период</label>
    <select id="period_select">
        <option value="week">За неделю</option>
        <option value="month">За месяц</option>
        <option value="quarter" selected>За квартал</option>
        <option value="custom">Другой интревал</option>
    </select>

    <button id="btn_search" class="btn btn_primary">Найти</button><br>

    <div id="custom_range_block" style="display: none; margin-top: 8px;">
        <label for="custom_start" style="font-weight: 700;">С:</label>
        <input type="date" id="custom_start" style="margin-right: 10px;">
        <label for="custom_end" style="font-weight: 700;">По:</label>
        <input type="date" id="custom_end">
    </div>

</div>

<div class="table_wrapper">
    <table id="results_table">
        <thead>
            <tr>
                <th>Сотрудник</th>
                <th>Кол-во переработок</th>
                <th>Детали</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div id="modal_overlay"></div>
<div id="modal_details">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <div id="modal_title" style="font-weight: 700;">Сотрудник: </div>
        <button id="modal_close" class="btn btn_danger">✖️</button>
    </div>
    <table id="details_table" style="width: 90%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="border: 1px solid #ccc; padding: 6px;">Дата</th>
                <th style="border: 1px solid #ccc; padding: 6px;">Кол-во часов</th>
                <th style="border: 1px solid #ccc; padding: 6px;">Работа вне офиса</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    function loadList(hours) {
        hours = parseFloat(hours) || 9;
        let period = $('#period_select').val();
        let start = '', end = '';

        if (period === 'custom') {
            start = $('#custom_start').val();
            end = $('#custom_end').val();

            if (!start || !end) {
                $('#results_table tbody').html('<tr><td colspan="3">Укажите даты для поиска</td></tr>');
                return;
            }
        }

        $('#results_table tbody').html('<tr><td colspan="3">Загрузка...</td></tr>');

        $.ajax({
            url: 'work_overtime.php',
            data: {action: 'load', hours: hours, period: period, start: start, end: end},
            dataType: 'json',
            success: function(resp) {
                if (resp.status !== 'success') {
                    alert('Ошибка: ' + (resp.message || 'неизвестная ошибка'));
                    $('#results_table tbody').html('');
                    return;
                }
                const rows = resp.data;
                if (!rows.length) {
                    $('#results_table tbody').html('<tr><td colspan="3">Нет сотрудников, подходящих под критерий.</td></tr>');
                    return;
                }
                let html = '';
                rows.forEach(r => {
                    html += `<tr>
                                <td>${escapeHtml(r.fio)}</td>
                                <td>${r.overtime_count}</td>
                                <td><button class="btn" onclick="showDetails(${r.id}, ${hours}, '${encodeURIComponent(r.fio)}')"> ➜ </button></td>
                            </tr>`;
                });
                $('#results_table tbody').html(html);
            },
            error: function(xhr, status, err) {
                console.error(err);
                alert('Сервер недоступен');
                $('#results_table tbody').html('');
            }
        });
    }

    $('#btn_search').on('click', function() {
        const hours = $('#hours_input').val();
        loadList(hours);
    });

    $('#period_select').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom_range_block').show();
        } else {
            $('#custom_range_block').hide();
        }
        const hours = $('#hours_input').val();
        loadList(hours);

    });

    loadList($('#hours_input').val());

    $('#modal_close, #modal_overlay').on('click', function() {
        closeModal();
    });
});

function showDetails(empId, hours, fioEncoded) {
    hours = parseFloat(hours) || 9;
    const period = $('#period_select').val() || 'quarter';
    var fio = decodeURIComponent(fioEncoded || '');
    let start = '', end = '';

    if (period === 'custom') {
        start = $('#custom_start').val();
        end = $('#custom_end').val();
    }
    $('#modal_title').text('Сотрудник: ' + fio);
    $('#details_table tbody').html('<tr><td colspan="3">Загрузка...</td></tr>');
    $('#modal_overlay').show();
    $('#modal_details').show();

    $.ajax({
        url: 'work_overtime.php',
        data: {action: 'details', id: empId, hours: hours, period: period, start: start, end: end},
        dataType: 'json',
        success: function(resp) {
            if (resp.status !== 'success') {
                alert('Ошибка: ' + (resp.message || 'неизвестная ошибка'));
                $('#details_table tbody').html('');
                return;
            }
            const rows = resp.data;
            if (!rows.length) {
                $('#details_table tbody').html('<tr><td colspan="3">Нет записей.</td></tr>');
                return;
            }
            let html = '';
            rows.forEach(r => {
                html += `<tr>
                            <td style="border: 1px solid #ccc; padding: 6px;">${formatDate(r.date)}</td>
                            <td style="border: 1px solid #ccc; padding: 6px;">${r.hours_total}</td>
                            <td style="border: 1px solid #ccc; padding: 6px;">
                                ${r.outside_hours === '-' ? '' : r.outside_hours}
                            </td>
                        </tr>`;
            });
            $('#details_table tbody').html(html);
        },
        error: function() {
            alert('Ошибка запроса деталей');
            $('#details_table tbody').html('');
        }
    });
}

function closeModal() {
    $('#modal_details').hide();
    $('#modal_overlay').hide();
    $('#details_table tbody').html('');
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';

    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    return parts[2] + '.' + parts[1] + '.' + parts[0];
}

</script>

<?php 
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";
?>

</body>
</html>
