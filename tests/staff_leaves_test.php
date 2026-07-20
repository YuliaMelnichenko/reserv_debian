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

    $types = '';
    $params = array();
    $where = buildStaffLeavesArchiveQuery(156, 'Командировка', '2026-04-01', '2026-06-30', $types, $params);
    test_assert_same('isss', $types, 'Archive filters must preserve prepared-statement parameter types');
    test_assert_same(array(156, 'Командировка', '2026-06-30', '2026-04-01'), $params, 'Archive dates must use overlap order');
    test_assert_same(true, strpos($where, 'start_date <= ? AND stop_date >= ?') !== false, 'Archive filtering must include overlapping absences');

    $rowsXml = buildStaffLeavesArchiveSheetRows(
        array(array(
            'name' => 'Тест <Сотрудник>',
            'start_date' => '2026-07-18',
            'stop_date' => '2026-07-19',
            'total_days' => 2,
            'event' => 'Командировка',
        )),
        'Период',
        'Все сотрудники',
        'Командировки',
        '20.07.2026 12:00:00'
    );
    test_assert_same(true, strpos($rowsXml, 'Тест &lt;Сотрудник&gt;') !== false, 'XLSX values must be XML escaped');
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
