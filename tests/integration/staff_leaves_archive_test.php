<?php

require_once __DIR__ . '/../../inc/staff_leaves.php';

return function ($link) {
    $employeeId = 701;
    $filterStart = date('Y-m-d', strtotime('-7 days'));
    $filterStop = date('Y-m-d', strtotime('-5 days'));
    $leaveStart = date('Y-m-d', strtotime($filterStart . ' -3 days'));
    $leaveStop = date('Y-m-d', strtotime($filterStop . ' +3 days'));

    integration_seed_employee($link, $employeeId, 'Архив');
    test_assert_same(
        true,
        createStaffLeave($link, $employeeId, $leaveStart, $leaveStop, 'Командировка'),
        'A staff-leave archive fixture must be created'
    );

    $rows = fetchStaffLeavesArchiveRows(
        $link,
        $employeeId,
        'Командировка',
        $filterStart,
        $filterStop,
        0,
        true
    );

    test_assert_same(1, count($rows), 'An overlapping absence must be included in the selected archive period');
    test_assert_same($filterStart, $rows[0]['start_date'], 'Archive export must clip an absence to the selected start date');
    test_assert_same($filterStop, $rows[0]['stop_date'], 'Archive export must clip an absence to the selected stop date');
    test_assert_same(3, $rows[0]['calendar_days'], 'Archive metrics must count only calendar days inside the selected period');
};
