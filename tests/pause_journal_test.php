<?php

return function () {
    $controller = file_get_contents(__DIR__ . '/../ajax/get_pauses_by_user.php');

    test_assert_true(
        strpos($controller, 'inc/pause_journal.php') !== false,
        'The pause journal controller must load its data service'
    );
    test_assert_same(
        0,
        preg_match('/\bSELECT\b/i', $controller),
        'SQL must not return to the pause journal controller'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:get_all_add_work_info_by_user|get_superuser_name_by_id|get_user_name_by_id)\s*\(/', $controller),
        'The pause journal controller must not perform legacy or per-row lookups'
    );
    test_assert_true(
        strpos($controller, 'id=\"pause_approvement_table\"') !== false,
        'The existing pause table markup must remain available'
    );

    $service = file_get_contents(__DIR__ . '/../inc/pause_journal.php');
    test_assert_true(
        strpos($service, 'get_current_quarter_date_range') !== false,
        'Pause entries must be limited to the current quarter'
    );
    test_assert_true(
        strpos($service, 'time_journal_query_pause_journal') !== false,
        'The pause service must use the shared time journal repository'
    );

    $repository = file_get_contents(__DIR__ . '/../inc/time_journal_repository.php');
    test_assert_true(
        strpos($repository, 'AND $stopExpr > $startExpr') !== false,
        'The pause query must reject zero and inverted intervals'
    );
    test_assert_true(
        strpos($repository, 'LEFT JOIN employees supervisor') !== false,
        'The pause query must load supervisor names without per-row queries'
    );

    $detailPage = file_get_contents(__DIR__ . '/../pause_view_user.php');
    test_assert_true(
        strpos($detailPage, 'inc/pause_journal.php') !== false,
        'The full pause detail page must use the shared data service'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:get_all_add_work_info_by_user|get_superuser_name_by_id|get_user_name_by_id)\s*\(/', $detailPage),
        'The full pause detail page must not perform legacy or per-row lookups'
    );
    test_assert_true(
        strpos($detailPage, 'notification-table-scroll notification-table-scroll-medium') !== false,
        'The existing pause detail layout must remain available'
    );

    $employeeTable = file_get_contents(__DIR__ . '/../ajax/get_pause_times_table.php');
    test_assert_true(
        strpos($employeeTable, 'inc/pause_journal.php') !== false,
        'The employee pause table must use the shared data service'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:SELECT|db_query|get_superuser_name_by_id|STARTDATE|STARTTIME|STOPTIME)\b/i', $employeeTable),
        'The employee pause table must not perform SQL, per-row, or legacy lookups'
    );
    test_assert_true(
        strpos($employeeTable, 'Текущий квартал:') !== false,
        'The existing current-quarter heading must remain available'
    );
};
