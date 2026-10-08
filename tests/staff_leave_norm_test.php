<?php

require_once __DIR__ . '/../inc/work_calendar.php';

return function () {
    $dates = array('2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08', '2026-10-09');
    $baseNorms = array_fill(0, 5, 4 * 3600);
    $allLeave = array('Отпуск', 'Отпуск', 'Отпуск', 'Отпуск', 'Отпуск');

    list($before, $after) = calculate_staff_leave_norms($dates, $baseNorms, $allLeave, 20, array());
    test_assert_same(array_fill(0, 5, 8 * 3600), $before, 'A full week of leave must count as 40 hours');
    test_assert_same(array_fill(0, 5, 0), $after, 'A full week of leave must leave no hours to work');

    list($before, $after) = calculate_staff_leave_norms(
        $dates, array_fill(0, 5, (36 / 5) * 3600), $allLeave, 36, array()
    );
    test_assert_same(40 * 3600, array_sum($before), 'A 36-hour employee must use full-day leave hours');
    test_assert_same(0, array_sum($after), 'A 36-hour employee must have no remaining plan during full leave');

    $partialLeave = array('NDF', 'NDF', 'Отпуск', 'Больничный', 'Отпуск');
    list($before, $after) = calculate_staff_leave_norms($dates, $baseNorms, $partialLeave, 20, array());
    test_assert_same(array(4, 4, 8, 8, 8), array_map(function ($seconds) {
        return intdiv($seconds, 3600);
    }, $before), 'A partial week must keep scheduled hours on worked days');
    test_assert_same(8 * 3600, array_sum($after), 'A partial week must leave only worked days in the plan');
    test_assert_same(24 * 3600, array_sum($before) - array_sum($after), 'Leave hours must balance the adjusted norm');

    $calendarDates = array(
        '2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08',
        '2026-10-09', '2026-10-10', '2026-10-11',
    );
    $calendarNorms = array(4 * 3600, 4 * 3600, 0, 3 * 3600, 4 * 3600, 4 * 3600, 0);
    $calendarTypes = array('2026-10-07' => 0, '2026-10-08' => 2, '2026-10-10' => 1);
    list($before, $after) = calculate_staff_leave_norms(
        $calendarDates, $calendarNorms, array_fill(0, 7, 'Больничный'), 20, $calendarTypes
    );
    test_assert_same(
        array(8, 8, 0, 7, 8, 8, 0),
        array_map(function ($seconds) { return intdiv($seconds, 3600); }, $before),
        'Holidays and weekends must stay excluded; shortened and transferred workdays must follow the calendar'
    );
    test_assert_same(array_fill(0, 7, 0), $after, 'Covered working dates must be removed from the plan');

    list($before, $after) = calculate_staff_leave_norms(
        $dates, array_fill(0, 5, 8 * 3600), $allLeave, 40, array()
    );
    test_assert_same(array_fill(0, 5, 8 * 3600), $before, 'A 40-hour employee must retain the existing norm');
    test_assert_same(array_fill(0, 5, 0), $after, 'A 40-hour employee must retain the existing absence deduction');

    list($before, $after) = calculate_staff_leave_norms(
        $dates, array_fill(0, 5, 0), $allLeave, 0, array()
    );
    test_assert_same(array_fill(0, 5, 0), $before, 'An employee without a configured rate must not gain a 40-hour norm');
    test_assert_same(array_fill(0, 5, 0), $after, 'An employee without a configured rate must keep a zero plan');

    $report = file_get_contents(__DIR__ . '/../inc/report_period_statistics.php');
    test_assert_true(
        strpos($report, 'list($days_norm_before_leaves, $days_norm) = calculate_staff_leave_norms(') !== false,
        'The time report must use the adjusted norm and deduction together'
    );
};
