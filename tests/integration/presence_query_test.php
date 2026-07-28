<?php

require_once __DIR__ . '/../../inc/index_page_service.php';

return function ($link) {
    integration_seed_employee($link, 101, 'Альфа');
    integration_seed_employee($link, 102, 'Бета');
    integration_seed_employee($link, 103, 'Гамма');
    $today = date('Y-m-d');

    $visitsCreated = db_execute(
        $link,
        "INSERT INTO visiting (
            ID, user_id, in_dt, eat_start_dt, eat_stop_dt, out_dt, state
         ) VALUES
            (1, 102, ?, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2),
            (2, 103, ?, '0000-00-00 00:00:00', '0000-00-00 00:00:00', ?, 0)",
        'sss',
        array($today . ' 08:00:00', $today . ' 08:10:00', $today . ' 17:00:00')
    );
    test_assert_same(true, $visitsCreated, 'Integration presence fixtures must be created');

    $rows = index_fetch_presence_rows($link);
    $byId = array();

    foreach ($rows as $row) {
        $byId[(int)$row[9]] = $row;
    }

    test_assert_same(0, (int)$byId[101][12], 'Employee at home must receive home sort order');
    test_assert_same(1, (int)$byId[102][12], 'Employee at work must receive active sort order');
    test_assert_same(2, (int)$byId[103][12], 'Employee who left must receive final sort order');
    test_assert_same('08:00', $byId[102][1], 'Batch visit query must preserve arrival time');
    test_assert_same('17:00', $byId[103][2], 'Batch visit query must preserve leave time');
};
