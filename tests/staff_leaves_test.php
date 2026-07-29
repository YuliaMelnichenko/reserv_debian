<?php

require_once __DIR__ . '/../inc/staff_leaves.php';
require_once __DIR__ . '/../inc/staff_leaves_export.php';

return function () {
    test_assert_same(
        2,
        getStaffLeaveDaysCount('2026-07-18', '2026-07-19'),
        'A business trip spanning Saturday and Sunday must include both weekend days'
    );
    test_assert_same(
        array('2026-07-01', '2026-07-20'),
        getArchivePeriodDates(4, '', '', '2026-07-20'),
        'The current-quarter archive period must begin on July 1'
    );
    test_assert_same(
        array('2026-04-01', '2026-06-30'),
        getArchivePeriodDates(5, '', '', '2026-07-20'),
        'The previous-quarter archive period must cover April through June'
    );
    test_assert_same(
        array('2026-06-29', '2026-06-30'),
        getArchivePeriodDates(7, '2026-06-29', '2026-06-30', '2026-07-20'),
        'A manual archive period must preserve valid dates'
    );
    test_assert_same('Командировка', normalizeStaffLeaveEvent(' Командировка '), 'A valid leave event must be trimmed');

    test_assert_same(
        array(
            'calendar_days' => 7,
            'work_days' => 4,
            'work_hours' => 31.0,
        ),
        calculateStaffLeaveArchiveMetrics(
            '2026-07-20',
            '2026-07-26',
            40,
            array(
                '2026-07-22' => 0,
                '2026-07-24' => 2,
            )
        ),
        'Archive metrics must separate calendar days, weekdays, holidays, and shortened-day hours'
    );

    $types = '';
    $params = array();
    $where = buildStaffLeavesArchiveQuery(156, 'Командировка', '2026-04-01', '2026-06-30', $types, $params);
    test_assert_same('isss', $types, 'Archive filters must preserve prepared-statement parameter types');
    test_assert_same(array(156, 'Командировка', '2026-06-30', '2026-04-01'), $params, 'Archive dates must use overlap order');
    test_assert_same(true, strpos($where, 'start_date <= ? AND stop_date >= ?') !== false, 'Archive filtering must include overlapping absences');

    $rowsXml = buildStaffLeavesArchiveSheetRows(
        array(array(
            'name' => 'Тест <Сотрудник>',
            'excel_name' => 'Тест <Сотрудник> Отчество',
            'start_date' => '2026-07-18',
            'stop_date' => '2026-07-19',
            'total_days' => 2,
            'calendar_days' => 2,
            'work_days' => 0,
            'work_hours' => 0,
            'event' => 'Командировка',
        )),
        'Период',
        'Все сотрудники',
        'Командировки',
        '20.07.2026 12:00:00'
    );
    test_assert_same(true, strpos($rowsXml, 'Тест &lt;Сотрудник&gt; Отчество') !== false, 'XLSX names must include the patronymic and be XML escaped');
    test_assert_same(true, strpos($rowsXml, 'Календарные дни') !== false, 'XLSX must contain a calendar-day column');
    test_assert_same(true, strpos($rowsXml, 'Рабочие дни') !== false, 'XLSX must contain a workday column');
    test_assert_same(true, strpos($rowsXml, 'Рабочие часы') !== false, 'XLSX must contain a work-hours column');
    test_assert_same(true, strpos($rowsXml, 'Командировка') !== false, 'XLSX rows must preserve the leave event');

    $exceptionThrown = false;
    try {
        normalizeStaffLeaveRange('2026-07-20', '2026-07-19');
    }
    catch (InvalidArgumentException $e) {
        $exceptionThrown = true;
    }
    test_assert_same(true, $exceptionThrown, 'A reversed leave period must be rejected');
};
