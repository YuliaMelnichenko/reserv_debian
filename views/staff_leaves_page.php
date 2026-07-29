<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <link rel="stylesheet" href="style/main.css">
    </head>
    <body class="app-page">
        <script type="text/javascript" src="lib/jquery/jquery.js"></script>
        <script type="text/javascript" src="js/tory.js?v=20260729-layout"></script>

<?php
echo "<div align=\"left\">";
echo "<table class=\"staff-leaves-page-table\" border=0>";
echo "<tr>";
echo "<td class=\"staff-leaves-nav-cell\" bordercolor=\"#888888\">";

include_once dirname(__DIR__) . "/navigate.php";

echo "</td>";


echo "<td class=\"vac staff-leaves-content-cell\" bordercolor=\"#888888\">";

echo "<h5 class=\"dark\"><br>/Больничные и отпуска сотрудников <br></h5>";

echo "<div id=\"event_buttons\">";
    echo "<div id=\"events\">";
    echo "<button id=\"btn_sick\" class=\"event-switch\" onclick=\"\">Больничные</button>";
    echo "<button id=\"btn_vacations\" class=\"event-switch\" onclick=\"\">Отпуска</button>";
    echo "<button id=\"btn_business_trip\" class=\"event-switch\" onclick=\"\">Командировки</button>";
    echo "<button id=\"btn_archive\" class=\"event-switch\" onclick=\"loadArchive();\">Архив</button>";
    echo "</div>";
    echo "<div id=\"add_info_block\">";
        echo "<button id=\"btn_add\" title=\"Добавить запись\">";
            echo "<img src=\"img/plus.png\" alt=\"Добавить запись\" height=\"24\">";
        echo "</button>";
    echo "</div>";
echo "</div>";
echo "<div id=\"archive_filters\">";

    echo "<span style=\"font-family: Arial,sans; font-size: 13px; font-weight: 700; margin-right:5px;\">Сотрудник:</span>";
    echo "<select id=\"archive_employee_filter\" class=\"flat\" style=\"width:160px; margin-right:15px;\">";
        echo "<option value=\"0\">Все сотрудники</option>";
        foreach (getEmployees($link) as $id => $fio) {
            echo "<option value=\"" . intval($id) . "\">" . html_escape($fio) . "</option>";
        }
    echo "</select>";

    echo "<span style=\"font-family: Arial,sans; font-size: 13px; font-weight: 700; margin-right:5px;\">Дата:</span>";
    echo "<select id=\"archive_period_filter\" class=\"flat\" style=\"width:170px; margin-right:15px;\" onchange=\"toggleArchiveManualPeriod();\">";
        echo "<option value=\"0\">Все даты</option>";
        echo "<option value=\"1\">С начала недели</option>";
        echo "<option value=\"2\">С начала месяца</option>";
        echo "<option value=\"3\">За предыдущий месяц</option>";
        echo "<option value=\"4\" selected>С начала квартала</option>";
        echo "<option value=\"5\">За предыдущий квартал</option>";
        echo "<option value=\"7\">Задать вручную</option>";
    echo "</select>";

    echo "<span id=\"archive_manual_period\" style=\"display:none; margin-right:8px;\">";
        echo "<input id=\"archive_start_date_filter\" type=\"date\" style=\"width:110px;\">";
        echo " - ";
        echo "<input id=\"archive_stop_date_filter\" type=\"date\" style=\"width:110px;\">";
    echo "</span>";

    echo "<span style=\"font-family: Arial,sans; font-size: 13px; font-weight: 700; margin-right:5px;\">Событие:</span>";
    echo "<select id=\"archive_event_filter\" class=\"flat\" style=\"width:130px; margin-right:15px;\">";
        echo "<option value=\"\">Все события</option>";
        echo "<option value=\"Отпуск\">Отпуска</option>";
        echo "<option value=\"Больничный\">Больничные</option>";
        echo "<option value=\"Командировка\">Командировки</option>";
    echo "</select>";

    echo "<button class=\"button_style\" style=\"font-size: 90%; width:90px; height:23px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"loadArchive();\">Обновить</button>";
    echo "<button class=\"button_style\" title=\"Выгрузить архив в Excel\" style=\"font-size: 90%; width:80px; height:23px; margin-left:40px; background-color:#d9ead3; border:1px solid #888888;\" onclick=\"openArchiveExcelPreview();\">";
        echo "<img src=\"img/excel.svg\" alt=\"Excel\" height=\"16\" style=\"vertical-align:middle; margin-right:3px;\" onerror=\"this.style.display='none';\">";
    echo "Excel";
    echo "</button>";

echo "</div>";
?>

<div class="leave_table_wrapper">
    <table id="leave_table">
        <thead>
            <tr>
                <th>Сотрудник</th>
                <th>Дата начала</th>
                <th>Дата окончания</th>
                <th>Кол-во дней</th>
                <th>Событие</th>
                <th></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

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
                <label style="font-family: Arial,sans; font-size: 13px; color: #333333; font-weight: 700; margin-bottom: 5px;">Дата окончания:</label>
                <input type="date" name="stop_date" required>
            </div>
            <div class="modal_labels" id="selectEventBlock">
                <label style="font-family: Arial,sans; font-size: 13px; color: #333333; font-weight: 700; margin-bottom: 5px;">Событие:</label>
                <select style="width: 120px;" name="event" required>
                    <option value="">Выберите...</option>
                    <option value="Отпуск">Отпуск</option>
                    <option value="Больничный">Больничный</option>
                    <option value="Командировка">Командировка</option>
                </select>
            </div>
        </div>
        <div id="modal_form_btn">
            <button type="submit" style="cursor: pointer; font-size: 100%; width:100px; height:25px; background-color:#f8d888; border:1px solid #888888;">Сохранить</button>
            <button type="button" style="cursor: pointer; font-size: 100%; width:100px; height:25px; background-color:#ff7979; border:1px solid #888888;" onclick="closeModal()">Отмена</button>
        </div>
    </form>
</div>

<div id="archiveExcelPreviewOverlay" style="display:none;">
    <div id="archiveExcelPreviewWindow">
        <div id="archiveExcelPreviewHeader">
            <span>Предпросмотр выгрузки в Excel</span>
            <button type="button" onclick="closeArchiveExcelPreview()">×</button>
        </div>

        <div id="archiveExcelPreviewFilters"></div>

        <div id="archiveExcelPreviewNote">
            В предпросмотре показаны первые 50 строк. В Excel будут выгружены все строки с учетом текущих фильтров.
        </div>

        <div id="archiveExcelPreviewTable"></div>

        <div id="archiveExcelPreviewActions">
            <button type="button" onclick="downloadArchiveExcel()" style="background-color: #f8d888">Выгрузить в Excel</button>
            <button type="button" onclick="closeArchiveExcelPreview()">Отмена</button>
        </div>
    </div>
</div>

<script type="text/javascript" src="js/staff-leaves.js?v=20260729-layout"></script>

<?php
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script>

</body>
</html>
