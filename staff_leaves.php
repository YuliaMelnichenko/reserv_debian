<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
include_once __DIR__ . "/funcs.php";
require_once __DIR__ . '/inc/access.php';
save_last_location("time_add.php");
require_page_staff_leaves_access();
include __DIR__ . "/php_tori/connect.php";

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

    $stmt = mysqli_prepare($link, "SELECT * FROM staff_leaves WHERE event = ? AND stop_date >= CURDATE() ORDER BY fio ASC, start_date ASC, stop_date ASC");    
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

function getArchivePeriodDates($periodType, $startDateManual, $stopDateManual) {
    $currDate = date('Y-m-d');

    if ($periodType == 0) {
        return ["", ""];
    }

    if ($periodType == 1) {
        $weekDay = (int)date('N', strtotime($currDate));
        $start = date('Y-m-d', strtotime("-" . ($weekDay - 1) . " days", strtotime($currDate)));

        return [$start, $currDate];
    }

    if ($periodType == 2) {
        return [date('Y-m-01', strtotime($currDate)), $currDate];
    }

    if ($periodType == 3) {
        $prevMonthDate = strtotime('first day of previous month', strtotime($currDate));

        return [
            date('Y-m-01', $prevMonthDate),
            date('Y-m-t', $prevMonthDate)
        ];
    }

    if ($periodType == 4) {
        $month = (int)date('n', strtotime($currDate));
        $year = (int)date('Y', strtotime($currDate));

        if ($month >= 1 && $month <= 3) {
            return ["$year-01-01", $currDate];
        }

        if ($month >= 4 && $month <= 6) {
            return ["$year-04-01", $currDate];
        }

        if ($month >= 7 && $month <= 9) {
            return ["$year-07-01", $currDate];
        }

        return ["$year-10-01", $currDate];
    }

    if ($periodType == 5) {
        $month = (int)date('n', strtotime($currDate));
        $year = (int)date('Y', strtotime($currDate));
        $currentQuarter = (int)ceil($month / 3);
        $previousQuarter = $currentQuarter - 1;

        if ($previousQuarter <= 0) {
            $previousQuarter = 4;
            $year--;
        }

        $startMonth = ($previousQuarter - 1) * 3 + 1;
        $start = sprintf('%04d-%02d-01', $year, $startMonth);
        $stop = date('Y-m-t', strtotime($start . ' +2 months'));

        return [$start, $stop];
    }

    if ($periodType == 7) {
        return [$startDateManual, $stopDateManual];
    }

    return ["", ""];
}

function formatArchiveDateRu($dateStr) {
    if ($dateStr == "") {
        return "";
    }

    $time = strtotime($dateStr);

    if ($time === false) {
        return $dateStr;
    }

    return date("d.m.Y", $time);
}

function getArchivePeriodFilterName($periodType) {
    switch ((int)$periodType) {
        case 1:
            return "С начала недели";
        case 2:
            return "С начала месяца";
        case 3:
            return "За предыдущий месяц";
        case 4:
            return "С начала квартала";
        case 5:
            return "За предыдущий квартал";
        case 7:
            return "Задать вручную";
        default:
            return "Все даты";
    }
}

function getArchivePeriodTitle($periodType, $filterStartDate, $filterStopDate) {
    $periodName = getArchivePeriodFilterName($periodType);

    if ($periodName == "Все даты") {
        return $periodName;
    }

    if ($filterStartDate == "" || $filterStopDate == "") {
        return $periodName;
    }

    return $periodName . " (" . formatArchiveDateRu($filterStartDate) . " - " . formatArchiveDateRu($filterStopDate) . ")";
}

function getArchiveEventTitle($event) {
    if ($event == "") {
        return "Все события";
    }

    return $event;
}

function getArchiveEmployeeTitle($link, $employeeId) {
    if ($employeeId <= 0) {
        return "Все сотрудники";
    }

    $stmt = mysqli_prepare($link, "SELECT surname, firstname FROM employees WHERE id = ? LIMIT 1");

    if (!$stmt) {
        return "Выбранный сотрудник";
    }

    mysqli_stmt_bind_param($stmt, 'i', $employeeId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        return $row['surname'] . ' ' . $row['firstname'];
    }

    return "Выбранный сотрудник";
}

function buildStaffLeavesArchiveQuery($employeeId, $event, $filterStartDate, $filterStopDate, &$types, &$params) {
    $where = ["stop_date < CURDATE()"];
    $params = [];
    $types = "";

    if ($employeeId > 0) {
        $where[] = "user_id = ?";
        $params[] = $employeeId;
        $types .= "i";
    }

    if ($event !== '') {
        $where[] = "event = ?";
        $params[] = $event;
        $types .= "s";
    }

    if ($filterStartDate !== '' && $filterStopDate !== '') {
        $where[] = "start_date <= ? AND stop_date >= ?";
        $params[] = $filterStopDate;
        $params[] = $filterStartDate;
        $types .= "ss";
    }

    return " WHERE " . implode(" AND ", $where);
}

function fetchStaffLeavesArchiveRows($link, $employeeId, $event, $filterStartDate, $filterStopDate, $limit) {
    $types = "";
    $params = [];

    $whereSql = buildStaffLeavesArchiveQuery(
        $employeeId,
        $event,
        $filterStartDate,
        $filterStopDate,
        $types,
        $params
    );

    $sql = "
        SELECT *
        FROM staff_leaves
        $whereSql
        ORDER BY fio ASC, start_date DESC, stop_date DESC
    ";

    if ($limit > 0) {
        $sql .= " LIMIT " . (int)$limit;
    }

    $stmt = mysqli_prepare($link, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($link));
    }

    if (count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        throw new Exception(mysqli_error($link));
    }

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $start = strtotime($row['start_date']);
        $stop = strtotime($row['stop_date']);
        $days = round(($stop - $start) / 86400 + 1);

        $rows[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'fio' => $row['fio'],
            'name' => $row['fio'],
            'start_date' => $row['start_date'],
            'stop_date' => $row['stop_date'],
            'event' => $row['event'],
            'total_days' => $days
        ];
    }

    return $rows;
}

function escapeExcelValue($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function escapeXlsxValue($value) {
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, "UTF-8");
}

function getXlsxColumnName($columnIndex) {
    $columnName = "";

    while ($columnIndex > 0) {
        $mod = ($columnIndex - 1) % 26;
        $columnName = chr(65 + $mod) . $columnName;
        $columnIndex = (int)(($columnIndex - $mod) / 26);
    }

    return $columnName;
}

function buildXlsxCell($rowIndex, $columnIndex, $value, $styleIndex = 0) {
    $cellRef = getXlsxColumnName($columnIndex) . $rowIndex;
    $styleAttr = $styleIndex > 0 ? ' s="' . (int)$styleIndex . '"' : "";

    return '<c r="' . $cellRef . '" t="inlineStr"' . $styleAttr . '><is><t>' . escapeXlsxValue($value) . '</t></is></c>';
}

function buildXlsxRow($rowIndex, $values, $styleIndex = 0) {
    $cells = "";

    for ($i = 0; $i < count($values); $i++) {
        $cells .= buildXlsxCell($rowIndex, $i + 1, $values[$i], $styleIndex);
    }

    return '<row r="' . $rowIndex . '">' . $cells . '</row>';
}

function sendStaffLeavesArchiveXlsx($rows, $periodTitle, $employeeTitle, $eventTitle, $exportTime) {
    if (!class_exists('ZipArchive')) {
        throw new Exception("На сервере не установлен PHP ZipArchive. Для безопасной выгрузки .xlsx нужен пакет php-zip.");
    }

    $sheetRows = "";
    $sheetRows .= buildXlsxRow(1, Array("Архив отсутствий сотрудников"), 1);
    $sheetRows .= buildXlsxRow(2, Array("Временной промежуток", $periodTitle), 2);
    $sheetRows .= buildXlsxRow(3, Array("Сотрудник", $employeeTitle), 2);
    $sheetRows .= buildXlsxRow(4, Array("Событие", $eventTitle), 2);
    $sheetRows .= buildXlsxRow(5, Array("Дата выгрузки", $exportTime), 2);
    $sheetRows .= buildXlsxRow(6, Array(""));
    $sheetRows .= buildXlsxRow(7, Array("ФИО", "Дата начала", "Дата окончания", "Кол-во дней", "Событие"), 3);

    $rowIndex = 8;

    foreach ($rows as $row) {
        $sheetRows .= buildXlsxRow(
            $rowIndex,
            Array(
                $row["name"],
                formatArchiveDateRu($row["start_date"]),
                formatArchiveDateRu($row["stop_date"]),
                $row["total_days"],
                $row["event"]
            ),
            0
        );

        $rowIndex++;
    }

    $mergeCells = '
        <mergeCells count="5">
            <mergeCell ref="A1:E1"/>
            <mergeCell ref="B2:E2"/>
            <mergeCell ref="B3:E3"/>
            <mergeCell ref="B4:E4"/>
            <mergeCell ref="B5:E5"/>
        </mergeCells>
    ';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <cols>
        <col min="1" max="1" width="34" customWidth="1"/>
        <col min="2" max="2" width="16" customWidth="1"/>
        <col min="3" max="3" width="16" customWidth="1"/>
        <col min="4" max="4" width="14" customWidth="1"/>
        <col min="5" max="5" width="18" customWidth="1"/>
    </cols>
    <sheetData>' . $sheetRows . '</sheetData>
    ' . $mergeCells . '
</worksheet>';

    $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>';

    $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';

    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Архив" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';

    $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';

    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="3">
        <font><sz val="10"/><name val="Arial"/></font>
        <font><b/><sz val="14"/><name val="Arial"/></font>
        <font><b/><sz val="10"/><name val="Arial"/></font>
    </fonts>
    <fills count="4">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFEEEEEE"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFD9EAD3"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FF888888"/></left>
            <right style="thin"><color rgb="FF888888"/></right>
            <top style="thin"><color rgb="FF888888"/></top>
            <bottom style="thin"><color rgb="FF888888"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="4">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    </cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';

    $createdIso = date("c");

    $coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>Архив отсутствий сотрудников</dc:title>
    <dc:creator>TORI</dc:creator>
    <cp:lastModifiedBy>TORI</cp:lastModifiedBy>
    <dcterms:created xsi:type="dcterms:W3CDTF">' . escapeXlsxValue($createdIso) . '</dcterms:created>
    <dcterms:modified xsi:type="dcterms:W3CDTF">' . escapeXlsxValue($createdIso) . '</dcterms:modified>
</cp:coreProperties>';

    $appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
    <Application>TORI</Application>
</Properties>';

    $tmpFile = tempnam(sys_get_temp_dir(), "staff_leaves_xlsx_");

    if ($tmpFile === false) {
        throw new Exception("Не удалось создать временный файл XLSX.");
    }

    $zip = new ZipArchive();

    if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
        throw new Exception("Не удалось открыть временный ZIP-файл XLSX.");
    }

    $zip->addFromString("[Content_Types].xml", $contentTypesXml);
    $zip->addFromString("_rels/.rels", $relsXml);
    $zip->addFromString("docProps/core.xml", $coreXml);
    $zip->addFromString("docProps/app.xml", $appXml);
    $zip->addFromString("xl/workbook.xml", $workbookXml);
    $zip->addFromString("xl/_rels/workbook.xml.rels", $workbookRelsXml);
    $zip->addFromString("xl/styles.xml", $stylesXml);
    $zip->addFromString("xl/worksheets/sheet1.xml", $sheetXml);
    $zip->close();

    while (ob_get_level()) {
        ob_end_clean();
    }

    $fileName = "staff_leaves_archive_" . date("Y-m-d_H-i-s") . ".xlsx";

    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header("Content-Length: " . filesize($tmpFile));
    header("Cache-Control: max-age=0");
    header("Pragma: public");

    readfile($tmpFile);
    unlink($tmpFile);
}

if (isset($_GET['action']) && $_GET['action'] === 'archive') {
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        $employeeId = intval($_GET['employee_id'] ?? 0);
        $event = $_GET['event'] ?? '';
        $periodType = intval($_GET['period_type'] ?? 0);
        $startDateManual = $_GET['start_date'] ?? '';
        $stopDateManual = $_GET['stop_date'] ?? '';

        list($filterStartDate, $filterStopDate) = getArchivePeriodDates(
            $periodType,
            $startDateManual,
            $stopDateManual
        );

        $rows = fetchStaffLeavesArchiveRows(
            $link,
            $employeeId,
            $event,
            $filterStartDate,
            $filterStopDate,
            0
        );

        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo application_json_error('Staff leave archive at ' . __FILE__ . ':' . __LINE__, $e->getMessage());
    }

    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'archive_excel_preview') {
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        $employeeId = intval($_GET['employee_id'] ?? 0);
        $event = $_GET['event'] ?? '';
        $periodType = intval($_GET['period_type'] ?? 0);
        $startDateManual = $_GET['start_date'] ?? '';
        $stopDateManual = $_GET['stop_date'] ?? '';

        list($filterStartDate, $filterStopDate) = getArchivePeriodDates($periodType, $startDateManual, $stopDateManual);

        $rows = fetchStaffLeavesArchiveRows(
            $link,
            $employeeId,
            $event,
            $filterStartDate,
            $filterStopDate,
            50
        );

        echo json_encode([
            'status' => 'success',
            'filters' => [
                'period' => getArchivePeriodTitle($periodType, $filterStartDate, $filterStopDate),
                'employee' => getArchiveEmployeeTitle($link, $employeeId),
                'event' => getArchiveEventTitle($event)
            ],
            'rows' => $rows,
            'preview_limit' => 50
        ]);
    } catch (Throwable $e) {
        echo application_json_error('Staff leave archive preview at ' . __FILE__ . ':' . __LINE__, $e->getMessage());
    }

    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'archive_excel_export') {
    try {
        $employeeId = intval($_GET['employee_id'] ?? 0);
        $event = $_GET['event'] ?? '';
        $periodType = intval($_GET['period_type'] ?? 0);
        $startDateManual = $_GET['start_date'] ?? '';
        $stopDateManual = $_GET['stop_date'] ?? '';
        $exportTime = $_GET['export_time'] ?? date("d.m.Y H:i:s");

        list($filterStartDate, $filterStopDate) = getArchivePeriodDates($periodType, $startDateManual, $stopDateManual);

        $rows = fetchStaffLeavesArchiveRows(
            $link,
            $employeeId,
            $event,
            $filterStartDate,
            $filterStopDate,
            0
        );

        $periodTitle = getArchivePeriodTitle($periodType, $filterStartDate, $filterStopDate);
        $employeeTitle = getArchiveEmployeeTitle($link, $employeeId);
        $eventTitle = getArchiveEventTitle($event);

        sendStaffLeavesArchiveXlsx($rows, $periodTitle, $employeeTitle, $eventTitle, $exportTime);
    } catch (Throwable $e) {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header("Content-type: text/plain; charset=utf-8");
        echo application_error_message('Staff leave XLSX export at ' . __FILE__ . ':' . __LINE__, $e->getMessage());
    }

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    header('Content-Type: application/json');

    try {
        $id = intval($_POST['record_id'] ?? 0);
        $start = $_POST['start_date'] ?? '';
        $stop = $_POST['stop_date'] ?? '';
        $event = $_POST['event'] ?? '';

        if (!$id || !$start || !$stop) {
            throw new Exception('Поля заполнены некорректно');
        }

        $stmt = mysqli_prepare($link, "UPDATE staff_leaves SET start_date = ?, stop_date = ?, event = ? WHERE id = ? ");

        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса" . mysqli_error($link));
        }

        mysqli_stmt_bind_param($stmt, 'sssi', $start, $stop, $event, $id);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    header('Content-Type: application/json');

    try {
        $id = intval($_POST['record_id'] ?? 0);
        if (!$id) throw new Exception('Некорректный ID');

        $stmt = mysqli_prepare($link, "DELETE FROM staff_leaves WHERE id = ?");

        if (!$stmt) {
            throw new Exception('Ошибка подготовки запроса: ' . mysqli_error($link));
        }

        mysqli_stmt_bind_param($stmt, 'i', $id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Ошибка удаления: " . mysqli_error($link));
        }

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        error_log('Ошибка удаления: ' . $e->getMessage());
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

include_once __DIR__ . "/navigate.php";

echo "</td>";
   
$wholeWidth = 800;

echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

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
            echo "<option value=\"" . intval($id) . "\">" . htmlspecialchars($fio) . "</option>";
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
            <button type="button" onclick="downloadArchiveExcel()">Выгрузить в Excel</button>
            <button type="button" onclick="closeArchiveExcelPreview()">Отмена</button>
        </div>
    </div>
</div>

<script>
    let currentType = 'Отпуск';

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('btn_vacations').addEventListener('click', () => {
            currentType = 'Отпуск';
            loadLeaves(currentType);
        });
        document.getElementById('btn_sick').addEventListener('click', () => {
            currentType = 'Больничный';
            loadLeaves(currentType);
        });
        document.getElementById('btn_business_trip').addEventListener('click', () => {
            currentType = 'Командировка';
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

        initArchiveFilterEvents();
        loadLeaves(currentType);
    });

    function renderLeaveRowsWithMergedNames(tbody, data) {
        tbody.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td colspan="6" align="center">
                    Нет записей
                </td>
            `;

            tbody.appendChild(tr);
            return;
        }

        const groupedRows = groupRowsByEmployeeName(data);

        groupedRows.forEach(group => {
            group.rows.forEach((row, rowIndex) => {
                const tr = document.createElement('tr');

                const borderClass = rowIndex === 0 ? 'employee-group-border' : '';

                let nameCell = "";

                if (rowIndex === 0) {
                    nameCell = `
                        <td rowspan="${group.rows.length}" valign="middle" class="merged-fio-cell ${borderClass}">
                            ${escapeHtml(group.name)}
                        </td>
                    `;
                }

                tr.innerHTML = `
                    ${nameCell}
                    <td class="${borderClass}">${formatDate(row.start_date)}</td>
                    <td class="${borderClass}">${formatDate(row.stop_date)}</td>
                    <td class="${borderClass}">${row.total_days}</td>
                    <td class="${borderClass}">${escapeHtml(row.event)}</td>
                    <td class="${borderClass}">
                        <button id="btn_red" onclick="editLeave(${row.id})" title="Редактировать">
                            <img src="img/red2.png" alt="Редактировать" width="20" height="20">
                        </button>
                    </td>
                `;

                tbody.appendChild(tr);
            });
        });
    }

    function groupRowsByEmployeeName(data) {
        const groups = [];
        let currentGroup = null;

        data.forEach(row => {
            const name = row.name || "";

            if (currentGroup === null || currentGroup.name !== name) {
                currentGroup = {
                    name: name,
                    rows: []
                };

                groups.push(currentGroup);
            }

            currentGroup.rows.push(row);
        });

        return groups;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function loadLeaves (type) {
        document.getElementById('archive_filters').style.display = 'none';

        document.querySelectorAll('#event_buttons button.event-switch').forEach(btn => {
            btn.classList.remove('active');
        });

        if (type === 'Отпуск') {
            document.getElementById('btn_vacations').classList.add('active');
        } else if (type === 'Больничный') {
            document.getElementById('btn_sick').classList.add('active');
        } else if (type === 'Командировка') {
            document.getElementById('btn_business_trip').classList.add('active');
        }

        fetch('staff_leaves.php?action=load&type=' + encodeURIComponent(type))
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    const table = document.getElementById('leave_table');
                    const tbody = table.querySelector('tbody');
                    tbody.innerHTML = "";

                    renderLeaveRowsWithMergedNames(tbody, data);
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
        const eventBlock = document.getElementById('selectEventBlock');

        document.getElementById('addForm').reset();
        recordIdInput.value = '';
        nameInfo.textContent = '';

        if (mode === 'add') {
            title.textContent = 'Добавление записи';

            employeeBlock.style.display = 'flex';
            modal.style.width = '655px'
            modal.style.height = '120px'

            employeeSelect.required = true;
        } else if (mode === 'edit' && record) {
            title.textContent = 'Внесите корректировки';
            recordIdInput.value = record.id;

            modal.style.width = '450px'
            modal.style.height = '140px'

            employeeSelect.required = false;

            document.querySelector('[name="start_date"]').value = record.start_date;
            document.querySelector('[name="stop_date"]').value = record.stop_date;

            nameInfo.textContent = 'Сотрудник: ' + record.fio;

            employeeBlock.style.display = 'none';
        }
        modal.style.display = 'flex';
    }

    function confirmDelete (id) {
        if (!confirm('Вы уверены, что хотите удалить запись?')) return;

        fetch('staff_leaves.php', {
            method: 'POST',
            body: new URLSearchParams({
                action: 'delete',
                record_id: id
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast("запись удалена");
                loadLeaves(currentType || 'Отпуск');
            } else {
                alert('' + data.message);
            }
        })
        .catch(err => {
            console.error('Ошибка запроса: ', err);
            alert('Сервер недоступен');
        });
    }

function loadArchive() {
    currentType = 'Архив';

    document.querySelectorAll('#event_buttons button.event-switch').forEach(btn => {
        btn.classList.remove('active');
    });

    document.getElementById('btn_archive').classList.add('active');
    document.getElementById('archive_filters').style.display = 'block';

    if (!validateArchiveFilters(false)) {
        const table = document.getElementById('leave_table');

        if (table) {
            const tbody = table.querySelector('tbody');

            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="6" align="center">Выберите даты ручного периода</td></tr>';
            }

            table.style.display = 'table';
        }

        return;
    }

    const params = new URLSearchParams(getArchiveFilterParams());
    params.append('action', 'archive');

    fetch('staff_leaves.php?' + params.toString())
        .then(res => res.text())
        .then(text => {
            let data;

            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error('Ошибка JSON:', err);
                console.warn('Ответ сервера:', text);

                alert(
                    'Ошибка загрузки архива. Сервер вернул не JSON:\n\n' +
                    text.substring(0, 1000)
                );

                return;
            }

            if (data.error) {
                alert('Ошибка: ' + data.error);
                return;
            }

            const table = document.getElementById('leave_table');

            if (!table) {
                alert('Ошибка: таблица архива leave_table не найдена.');
                return;
            }

            const tbody = table.querySelector('tbody');

            if (!tbody) {
                alert('Ошибка: tbody таблицы архива не найден.');
                return;
            }

            tbody.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" align="center">Нет записей</td></tr>';
                table.style.display = 'table';
                return;
            }

            renderLeaveRowsWithMergedNames(tbody, data);
            table.style.display = 'table';
        })
        .catch(err => {
            console.error('Ошибка запроса архива:', err);
            alert('Ошибка запроса архива: ' + err);
        });
}

function getArchiveFilterParams() {
        const employeeId = document.getElementById('archive_employee_filter').value;
        const event = document.getElementById('archive_event_filter').value;
        const periodType = document.getElementById('archive_period_filter').value;
        const startDate = document.getElementById('archive_start_date_filter').value;
        const stopDate = document.getElementById('archive_stop_date_filter').value;

        return {
            employee_id: employeeId,
            event: event,
            period_type: periodType,
            start_date: startDate,
            stop_date: stopDate
        };
    }

    function validateArchiveFilters(showAlert = true) {
        const periodType = document.getElementById('archive_period_filter').value;
        const startDate = document.getElementById('archive_start_date_filter').value;
        const stopDate = document.getElementById('archive_stop_date_filter').value;

        if (periodType == 7) {
            if (!startDate || !stopDate) {
                if (showAlert) {
                    alert('Укажите дату начала и дату окончания периода.');
                }

                return false;
            }

            if (startDate > stopDate) {
                if (showAlert) {
                    alert('Дата начала периода не может быть позже даты окончания.');
                }

                return false;
            }
        }

        return true;
    }

    function initArchiveFilterEvents() {
        const filterIds = [
            'archive_employee_filter',
            'archive_event_filter',
            'archive_start_date_filter',
            'archive_stop_date_filter'
        ];

        filterIds.forEach(id => {
            const element = document.getElementById(id);

            if (element) {
                element.addEventListener('change', () => {
                    if (currentType === 'Архив') {
                        loadArchive();
                    }
                });
            }
        });
    }

    function openArchiveExcelPreview() {
        if (!validateArchiveFilters()) {
            return;
        }

        const params = new URLSearchParams(getArchiveFilterParams());
        params.append('action', 'archive_excel_preview');

        fetch('staff_leaves.php?' + params.toString())
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);

                    if (data.status !== 'success') {
                        alert('Ошибка: ' + data.message);
                        return;
                    }

                    renderArchiveExcelPreview(data);
                    document.getElementById('archiveExcelPreviewOverlay').style.display = 'flex';
                } catch (err) {
                    console.error('Ошибка JSON: ', err);
                    console.warn('Ответ сервера: ', text);
                    alert('Ошибка формирования предпросмотра. Проверь консоль.');
                }
            });
    }

    function renderArchiveExcelPreview(data) {
        let filtersHtml = '';

        filtersHtml += '<table class="archive-excel-filter-table">';
        filtersHtml += '<tr><td><b>Временной промежуток</b></td><td>' + escapeHtml(data.filters.period) + '</td></tr>';
        filtersHtml += '<tr><td><b>Сотрудник</b></td><td>' + escapeHtml(data.filters.employee) + '</td></tr>';
        filtersHtml += '<tr><td><b>Событие</b></td><td>' + escapeHtml(data.filters.event) + '</td></tr>';
        filtersHtml += '</table>';

        document.getElementById('archiveExcelPreviewFilters').innerHTML = filtersHtml;

        let tableHtml = '';

        tableHtml += '<table class="archive-excel-preview-table">';
        tableHtml += '<thead>';
        tableHtml += '<tr>';
        tableHtml += '<th>ФИО</th>';
        tableHtml += '<th>Дата начала</th>';
        tableHtml += '<th>Дата окончания</th>';
        tableHtml += '<th>Кол-во дней</th>';
        tableHtml += '<th>Событие</th>';
        tableHtml += '</tr>';
        tableHtml += '</thead>';
        tableHtml += '<tbody>';

        if (!Array.isArray(data.rows) || data.rows.length === 0) {
            tableHtml += '<tr><td colspan="5" align="center">Нет данных для выгрузки</td></tr>';
        } else {
            data.rows.forEach(row => {
                tableHtml += '<tr>';
                tableHtml += '<td>' + escapeHtml(row.name) + '</td>';
                tableHtml += '<td>' + formatDate(row.start_date) + '</td>';
                tableHtml += '<td>' + formatDate(row.stop_date) + '</td>';
                tableHtml += '<td>' + escapeHtml(row.total_days) + '</td>';
                tableHtml += '<td>' + escapeHtml(row.event) + '</td>';
                tableHtml += '</tr>';
            });
        }

        tableHtml += '</tbody>';
        tableHtml += '</table>';

        document.getElementById('archiveExcelPreviewTable').innerHTML = tableHtml;
    }

    function closeArchiveExcelPreview() {
        document.getElementById('archiveExcelPreviewOverlay').style.display = 'none';
    }

    function getUserExportTimeString() {
        const now = new Date();
        const pad = value => String(value).padStart(2, '0');

        return pad(now.getDate()) + '.' +
            pad(now.getMonth() + 1) + '.' +
            now.getFullYear() + ' ' +
            pad(now.getHours()) + ':' +
            pad(now.getMinutes()) + ':' +
            pad(now.getSeconds());
    }

    function downloadArchiveExcel() {
        if (!validateArchiveFilters()) {
            return;
        }

        const params = new URLSearchParams(getArchiveFilterParams());
        params.append('action', 'archive_excel_export');
        params.append('export_time', getUserExportTimeString());

        window.location = 'staff_leaves.php?' + params.toString();
        closeArchiveExcelPreview();
    }

    function toggleArchiveManualPeriod() {
        const periodType = document.getElementById('archive_period_filter').value;
        const manualPeriod = document.getElementById('archive_manual_period');

        if (periodType == 7) {
            manualPeriod.style.display = 'inline';
        } else {
            manualPeriod.style.display = 'none';
        }

        if (currentType === 'Архив') {
            loadArchive();
        }
    }

    function closeModal() {
        document.getElementById('modal').style.display = 'none';
        document.getElementById('addForm').reset();
    }

    function formatDate (dateStr) {
        const date = new Date(dateStr);
        const day = ('0' + date.getDate()).slice(-2);
        const month = ('0' + (date.getMonth() + 1)).slice(-2);
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    }

    function showToast (message = "✅ успешно", delay = 1500) {
        const toast = document.getElementById('toast');
        toast.textContent = message;

        toast.style.display = 'flex';

        setTimeout(() => {
            toast.style.display = 'none';

            if (currentType === 'Архив') {
                loadArchive();
            } else {
                loadLeaves(currentType || 'Отпуск');
            }
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
