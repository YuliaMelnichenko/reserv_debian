<?php 
ob_start();
require_once __DIR__ . '/inc/session.php';
include_once __DIR__ . "/funcs.php";
require_once __DIR__ . '/inc/access.php';
require_once __DIR__ . '/inc/overtime.php';
save_last_location("time_add.php");
require_page_work_overtime_access();
include __DIR__ . "/php_tori/connect.php";

// === AJAX: список сотрудников с количеством переработок >= hours (текущий квартал) ===
if (isset($_GET['action']) && $_GET['action'] === 'load') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $hours = normalizeOvertimeThreshold($_GET['hours'] ?? null);
        $period = $_GET['period'] ?? 'quarter';
        list($qstart, $qend) = getOvertimePeriodBounds(
            $period,
            $_GET['start'] ?? '',
            $_GET['end'] ?? ''
        );

        list($numbersSql, $addWorkDateSql, $addRangeSql, $addDurationSql) = overtimeAddTimeSqlParts();

        $sql = "
            SELECT 
                e.id AS emp_id,
                CONCAT_WS(' ', e.surname, e.firstname, e.lastname) AS fio,
                COUNT(t.work_date) AS overtime_days,
                ROUND(SUM(t.total_hours) - (? * COUNT(t.work_date)), 2) AS overtime_hours
            FROM employees e
            LEFT JOIN (
                SELECT d.user_id, d.work_date,
                    GREATEST(
                        0,
                        IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0) - IFNULL(p.pause_hours, 0)
                    ) AS total_hours
                FROM (
                    SELECT user_id, DATE(in_dt) AS work_date
                    FROM visiting
                    WHERE in_dt >= ? AND in_dt < ?
                    AND in_dt IS NOT NULL
                    AND in_dt != '0000-00-00 00:00:00'
                    AND out_dt IS NOT NULL
                    AND out_dt != '0000-00-00 00:00:00'
                    AND out_dt > in_dt
                    GROUP BY user_id, DATE(in_dt)
                    
                    UNION
                    
                    SELECT a.USERID AS user_id, $addWorkDateSql AS work_date
                    FROM ADD_TIME a
                    JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                    WHERE $addRangeSql
                    AND a.REASON IN (1, 2, 3, 4, 5)
                    GROUP BY a.USERID, $addWorkDateSql
                ) d 
                LEFT JOIN (
                    SELECT user_id, DATE(in_dt) AS work_date,
                        ROUND(SUM(
                                TIME_TO_SEC(TIMEDIFF(out_dt, in_dt))
                                - IF(
                                    eat_start_dt IS NULL
                                    OR eat_stop_dt IS NULL
                                    OR eat_start_dt = '0000-00-00 00:00:00'
                                    OR eat_stop_dt = '0000-00-00 00:00:00'
                                    OR eat_stop_dt <= eat_start_dt,
                                    0,
                                    TIME_TO_SEC(TIMEDIFF(eat_stop_dt, eat_start_dt))
                                )
                            ) / 3600, 2) AS office_hours
                    FROM visiting 
                    WHERE in_dt >= ? AND in_dt < ?
                    AND in_dt IS NOT NULL
                    AND in_dt != '0000-00-00 00:00:00'
                    AND out_dt IS NOT NULL
                    AND out_dt != '0000-00-00 00:00:00'
                    AND out_dt > in_dt
                    GROUP BY user_id, DATE(in_dt)
                ) v ON d.user_id = v.user_id AND d.work_date = v.work_date
                LEFT JOIN (
                    SELECT a.USERID AS user_id, $addWorkDateSql AS work_date,
                        ROUND(SUM($addDurationSql) / 3600, 2) AS outside_hours
                    FROM ADD_TIME a
                    JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                    WHERE $addRangeSql
                    AND a.REASON IN (1, 2, 3, 4, 5)
                    GROUP BY a.USERID, $addWorkDateSql
                ) a ON d.user_id = a.user_id AND d.work_date = a.work_date
                LEFT JOIN (
                    SELECT a.USERID AS user_id, $addWorkDateSql AS work_date,
                        ROUND(SUM($addDurationSql) / 3600, 2) AS pause_hours
                    FROM ADD_TIME a
                    JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                    WHERE $addRangeSql
                    AND a.REASON = -1
                    GROUP BY a.USERID, $addWorkDateSql
                ) p ON d.user_id = p.user_id AND d.work_date = p.work_date
                WHERE GREATEST(
                    0,
                    IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0) - IFNULL(p.pause_hours, 0)
                ) >= ?
            ) AS t ON e.id = t.user_id
            WHERE t.work_date IS NOT NULL
            GROUP BY e.id
            ORDER BY overtime_days DESC, fio ASC
        ";

        $stmt = mysqli_prepare($link, $sql);

        if (!$stmt) {
            throw new Exception('Ошибка подготовки запроса: ' . mysqli_error($link));
        }

        if (mysqli_stmt_param_count($stmt) !== 18) {
            throw new Exception('Количество плейсхолдеров не совпадает: ' . mysqli_stmt_param_count($stmt));
        }

        mysqli_stmt_bind_param($stmt, 
                                'dssssssssssssssssd',
                                    $hours, // 1
                                    $qstart, $qend, // 2-3 visiting (union)
                                        $qend, $qstart, $qstart, $qend, // 4-7 add_time (union)
                                        $qstart, $qend, // 8-9 visiting (join)
                                        $qend, $qstart, $qstart, $qend, // 10-13 add_time positive (join)
                                        $qend, $qstart, $qstart, $qend, // 14-17 add_time pause REASON=-1 (join)
                                        $hours          // 18 threshold
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
    } catch (Throwable $e) {
        echo application_json_error('Overtime list at ' . __FILE__ . ':' . __LINE__, $e->getMessage());
    }
    exit;
}

// === AJAX: детали по сотруднику — записи (дата + часы) с переработкой >= hours (текущий квартал) ===
if (isset($_GET['action']) && $_GET['action'] === 'details' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $empId = intval($_GET['id']);
        if ($empId <= 0) throw new Exception('Некорректный ID сотрудника');

        $hours = normalizeOvertimeThreshold($_GET['hours'] ?? null);
        $period = $_GET['period'] ?? 'quarter';
        list($qstart, $qend) = getOvertimePeriodBounds(
            $period,
            $_GET['start'] ?? '',
            $_GET['end'] ?? ''
        );

        list($numbersSql, $addWorkDateSql, $addRangeSql, $addDurationSql) = overtimeAddTimeSqlParts();

        $sql = "
            SELECT 
                d.work_date,
                ROUND(
                    GREATEST(
                        0,
                        IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0) - IFNULL(p.pause_hours, 0)
                    ),
                    2
                ) AS total_hours,
                ROUND(IFNULL(v.office_hours, 0), 2) AS office_hours,
                ROUND(IFNULL(a.outside_hours, 0), 2) AS outside_hours,
                ROUND(IFNULL(p.pause_hours, 0), 2) AS pause_hours
            FROM (
                SELECT DATE(in_dt) AS work_date
                FROM visiting
                WHERE user_id = ?
                AND in_dt >= ? AND in_dt < ?
                AND in_dt IS NOT NULL
                AND in_dt != '0000-00-00 00:00:00'
                GROUP BY DATE(in_dt)

                UNION
                
                SELECT $addWorkDateSql AS work_date
                FROM ADD_TIME a
                JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                WHERE a.USERID = ?
                AND $addRangeSql
                AND a.REASON IN (1, 2, 3, 4, 5)
                GROUP BY $addWorkDateSql
            ) d 
            LEFT JOIN (
                SELECT DATE(in_dt) AS work_date,
                    ROUND(SUM(
                        TIME_TO_SEC(TIMEDIFF(out_dt, in_dt))
                        - IF(
                            eat_start_dt IS NULL
                            OR eat_stop_dt IS NULL
                            OR eat_start_dt = '0000-00-00 00:00:00'
                            OR eat_stop_dt = '0000-00-00 00:00:00'
                            OR eat_stop_dt <= eat_start_dt,
                            0,
                            TIME_TO_SEC(TIMEDIFF(eat_stop_dt, eat_start_dt))
                        )
                    ) / 3600, 2) AS office_hours
                FROM visiting 
                WHERE user_id = ? 
                AND in_dt >= ? AND in_dt < ?
                AND in_dt IS NOT NULL
                AND in_dt != '0000-00-00 00:00:00'
                AND out_dt IS NOT NULL
                AND out_dt != '0000-00-00 00:00:00'
                AND out_dt > in_dt
                GROUP BY DATE(in_dt) 
            ) v ON d.work_date = v.work_date
            LEFT JOIN (
                SELECT $addWorkDateSql AS work_date,
                    ROUND(SUM($addDurationSql) / 3600, 2) AS outside_hours
                FROM ADD_TIME a
                JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                WHERE a.USERID = ?
                AND $addRangeSql
                AND a.REASON IN (1, 2, 3, 4, 5)
                GROUP BY $addWorkDateSql
            ) a ON d.work_date = a.work_date
            LEFT JOIN (
                SELECT $addWorkDateSql AS work_date,
                    ROUND(SUM($addDurationSql) / 3600, 2) AS pause_hours
                FROM ADD_TIME a
                JOIN ($numbersSql) n ON n.n <= DATEDIFF(DATE(a.STOP_DT), DATE(a.START_DT))
                WHERE a.USERID = ?
                AND $addRangeSql
                AND a.REASON = -1
                GROUP BY $addWorkDateSql
            ) p ON d.work_date = p.work_date
            WHERE GREATEST(
                0,
                IFNULL(v.office_hours, 0) + IFNULL(a.outside_hours, 0) - IFNULL(p.pause_hours, 0)
            ) >= ?
            ORDER BY d.work_date DESC
        ";
        $stmt = mysqli_prepare($link, $sql);
        if (!$stmt) throw new Exception('Ошибка подготовки запроса ' . mysqli_error($link));

        if (mysqli_stmt_param_count($stmt) !== 22) {
            throw new Exception('Количество плейсхолдеров не совпадает: ' . mysqli_stmt_param_count($stmt));
        }

        mysqli_stmt_bind_param($stmt, 
                                'ississssississssissssd',
                                    $empId, $qstart, $qend, // visiting (union)
                                        $empId, $qend, $qstart, $qstart, $qend, // add_time positive (union)
                                        $empId, $qstart, $qend, // visiting (join)
                                        $empId, $qend, $qstart, $qstart, $qend, // add_time positive (join)
                                        $empId, $qend, $qstart, $qstart, $qend, // add_time pause REASON=-1 (join)
                                        $hours                  // threshold
        );

        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'date' => $row['work_date'],
                'hours_total' => formatHours($row['total_hours']),
                'office_hours' => formatHours($row['office_hours']),
                'outside_hours' => formatHours($row['outside_hours']),
                'pause_hours' => formatHours($row['pause_hours'])
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $rows,
            'quarter_start' => $qstart,
            'quarter_end' => $qend
        ]);
    } catch (Throwable $e) {
        echo application_json_error('Overtime details at ' . __FILE__ . ':' . __LINE__, $e->getMessage());
    }
    exit;
}

?>

<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <title>Выгрузка переработок — текущий квартал</title>
    <link rel="stylesheet" href="style/main.css">
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
        <button id="modal_close" class="btn btn-danger">✖️</button>
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

<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" src="js/tory.js?v=20260709"></script>
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
                                ${r.outside_hours === '—' ? '' : r.outside_hours}
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
