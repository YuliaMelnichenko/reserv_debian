<?php

require_once __DIR__ . '/../../inc/staff_leaves.php';
require_once __DIR__ . '/../../inc/work_calendar.php';

return function ($link) {
    $employeeId = 712;
    integration_seed_employee($link, $employeeId, 'Неполная неделя');
    test_assert_true(
        db_execute($link, 'UPDATE employees SET rate = 20 WHERE ID = ?', 'i', array($employeeId)),
        'The reduced weekly rate must be stored in the employee record'
    );
    createStaffLeave($link, $employeeId, '2026-10-07', '2026-10-08', 'Отпуск');
    createStaffLeave($link, $employeeId, '2026-10-09', '2026-10-09', 'Больничный');

    $employee = db_fetch_one(db_query(
        $link, 'SELECT rate FROM employees WHERE ID = ?', 'i', array($employeeId)
    ));
    $dates = array('2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08', '2026-10-09');
    $events = get_staff_leave_events_by_days(
        $link, $employeeId, '2026-10-05', '2026-10-09', $dates
    );
    test_assert_same(
        array('NDF', 'NDF', 'Отпуск', 'Отпуск', 'Больничный'),
        $events,
        'The report must load both kinds of leave from staff_leaves'
    );

    list($before, $after) = calculate_staff_leave_norms(
        $dates,
        array_fill(0, 5, 4 * 3600),
        $events,
        (int)$employee['rate'],
        get_work_dayoff_types_by_range($link, '2026-10-05', '2026-10-09')
    );
    test_assert_same(32 * 3600, array_sum($before), 'The mixed week must have a 32-hour adjusted norm');
    test_assert_same(24 * 3600, array_sum($before) - array_sum($after), 'Absence must account for 24 hours');
    test_assert_same(8 * 3600, array_sum($after), 'Only two scheduled workdays must remain in the plan');
};
