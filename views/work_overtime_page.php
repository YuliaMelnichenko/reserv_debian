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
include_once dirname(__DIR__) . "/navigate.php";
echo "</td>";

$wholeWidth = 600;
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
<script type="text/javascript" src="js/work-overtime.js?v=20260723"></script>

<?php
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</div>";
?>

</body>
</html>
