<?php

require_once __DIR__ . '/../inc/index_page_service.php';

return function () {
    $mapped = index_map_rows_by_employee(array(
        array('user_id' => '10', 'state' => 'first'),
        array('user_id' => 20, 'state' => 'second'),
    ));

    test_assert_same('first', $mapped[10]['state'], 'Presence rows must be mapped by integer employee ID');
    test_assert_same('second', $mapped[20]['state'], 'Every employee presence row must remain available');

    list(, $homeOrder) = index_presence_status(10, null, null, null);
    list(, $workingOrder) = index_presence_status(
        10,
        array(
            'in_dt' => '2026-07-28 08:00:00',
            'eat_start_dt' => '0000-00-00 00:00:00',
            'eat_stop_dt' => '0000-00-00 00:00:00',
            'out_dt' => '0000-00-00 00:00:00',
        ),
        null,
        null
    );
    list(, $goneHomeOrder) = index_presence_status(
        10,
        array(
            'in_dt' => '2026-07-28 08:00:00',
            'eat_start_dt' => '2026-07-28 12:00:00',
            'eat_stop_dt' => '2026-07-28 13:00:00',
            'out_dt' => '2026-07-28 17:00:00',
        ),
        null,
        null
    );

    test_assert_same(0, $homeOrder, 'Employees at home must be sorted first');
    test_assert_same(1, $workingOrder, 'Employees at work must be sorted after employees at home');
    test_assert_same(2, $goneHomeOrder, 'Employees who left must be sorted last');

    $source = file_get_contents(__DIR__ . '/../inc/index_page_service.php');
    $loopStart = strpos($source, 'foreach (db_fetch_all($employeeResult) as $employee)');
    $loopStop = strpos($source, 'usort($employees', $loopStart);
    $employeeLoop = substr($source, $loopStart, $loopStop - $loopStart);

    test_assert_true($loopStart !== false && $loopStop !== false, 'Presence employee loop must remain detectable');
    test_assert_same(
        0,
        preg_match('/\bdb_query\s*\(/', $employeeLoop),
        'Presence rendering must not issue one query per employee'
    );
};
