<?php

function escapeXlsxValue($value)
{
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function getXlsxColumnName($columnIndex)
{
    $columnName = '';

    while ($columnIndex > 0) {
        $mod = ($columnIndex - 1) % 26;
        $columnName = chr(65 + $mod) . $columnName;
        $columnIndex = (int)(($columnIndex - $mod) / 26);
    }

    return $columnName;
}

function buildXlsxCell($rowIndex, $columnIndex, $value, $styleIndex = 0)
{
    $cellRef = getXlsxColumnName($columnIndex) . $rowIndex;
    $styleAttr = $styleIndex > 0 ? ' s="' . (int)$styleIndex . '"' : '';

    return '<c r="' . $cellRef . '" t="inlineStr"' . $styleAttr . '><is><t>' . escapeXlsxValue($value) . '</t></is></c>';
}

function buildXlsxRow($rowIndex, $values, $styleIndex = 0)
{
    $cells = '';

    foreach (array_values($values) as $columnIndex => $value) {
        $cells .= buildXlsxCell($rowIndex, $columnIndex + 1, $value, $styleIndex);
    }

    return '<row r="' . $rowIndex . '">' . $cells . '</row>';
}

function buildStaffLeavesArchiveSheetRows($rows, $periodTitle, $employeeTitle, $eventTitle, $exportTime)
{
    $sheetRows = '';
    $sheetRows .= buildXlsxRow(1, array('Архив отсутствий сотрудников'), 1);
    $sheetRows .= buildXlsxRow(2, array('Временной промежуток', $periodTitle), 2);
    $sheetRows .= buildXlsxRow(3, array('Сотрудник', $employeeTitle), 2);
    $sheetRows .= buildXlsxRow(4, array('Событие', $eventTitle), 2);
    $sheetRows .= buildXlsxRow(5, array('Дата выгрузки', $exportTime), 2);
    $sheetRows .= buildXlsxRow(6, array(''));
    $sheetRows .= buildXlsxRow(7, array('ФИО', 'Дата начала', 'Дата окончания', 'Кол-во дней', 'Событие'), 3);
    $rowIndex = 8;

    foreach ($rows as $row) {
        $sheetRows .= buildXlsxRow(
            $rowIndex,
            array(
                $row['name'],
                formatArchiveDateRu($row['start_date']),
                formatArchiveDateRu($row['stop_date']),
                $row['total_days'],
                $row['event'],
            )
        );
        $rowIndex++;
    }

    return $sheetRows;
}

function sendStaffLeavesArchiveXlsx($rows, $periodTitle, $employeeTitle, $eventTitle, $exportTime)
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('На сервере не установлен PHP ZipArchive. Для безопасной выгрузки .xlsx нужен пакет php-zip.');
    }

    $sheetRows = buildStaffLeavesArchiveSheetRows($rows, $periodTitle, $employeeTitle, $eventTitle, $exportTime);
    $createdIso = date('c');

    $files = array(
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>',
        'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Архив" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>',
        'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="3"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="14"/><name val="Arial"/></font><font><b/><sz val="10"/><name val="Arial"/></font></fonts>
<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEEEEEE"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9EAD3"/><bgColor indexed="64"/></patternFill></fill></fills>
<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FF888888"/></left><right style="thin"><color rgb="FF888888"/></right><top style="thin"><color rgb="FF888888"/></top><bottom style="thin"><color rgb="FF888888"/></bottom><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/></cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
        'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<cols><col min="1" max="1" width="34" customWidth="1"/><col min="2" max="3" width="16" customWidth="1"/><col min="4" max="4" width="14" customWidth="1"/><col min="5" max="5" width="18" customWidth="1"/></cols>
<sheetData>' . $sheetRows . '</sheetData>
<mergeCells count="5"><mergeCell ref="A1:E1"/><mergeCell ref="B2:E2"/><mergeCell ref="B3:E3"/><mergeCell ref="B4:E4"/><mergeCell ref="B5:E5"/></mergeCells></worksheet>',
        'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Архив отсутствий сотрудников</dc:title><dc:creator>TORI</dc:creator><cp:lastModifiedBy>TORI</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . escapeXlsxValue($createdIso) . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . escapeXlsxValue($createdIso) . '</dcterms:modified></cp:coreProperties>',
        'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>TORI</Application></Properties>',
    );

    $tmpFile = tempnam(sys_get_temp_dir(), 'staff_leaves_xlsx_');
    if ($tmpFile === false) {
        throw new RuntimeException('Не удалось создать временный файл XLSX.');
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
        @unlink($tmpFile);
        throw new RuntimeException('Не удалось открыть временный ZIP-файл XLSX.');
    }

    foreach ($files as $path => $content) {
        $zip->addFromString($path, $content);
    }
    $zip->close();

    while (ob_get_level()) {
        ob_end_clean();
    }

    $fileName = 'staff_leaves_archive_' . date('Y-m-d_H-i-s') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    readfile($tmpFile);
    unlink($tmpFile);
}
