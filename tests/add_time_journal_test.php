<?php

return function () {
    $controller = file_get_contents(__DIR__ . '/../ajax/get_add_times_by_user.php');

    test_assert_true(
        strpos($controller, 'inc/add_time_journal.php') !== false,
        'The remote work journal controller must load its data service'
    );
    test_assert_same(
        0,
        preg_match('/\bSELECT\b/i', $controller),
        'SQL must not return to the remote work journal controller'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:get_all_add_work_info_by_user|get_superuser_name_by_id|get_user_name_by_id)\s*\(/', $controller),
        'The remote work journal controller must not perform legacy or per-row lookups'
    );
    test_assert_true(
        strpos($controller, 'id=\"add_time_approvement_table\"') !== false,
        'The existing remote work table markup must remain available'
    );

    $service = file_get_contents(__DIR__ . '/../inc/add_time_journal.php');
    test_assert_true(
        strpos($service, 'time_journal_query_add_work_journal') !== false,
        'The data service must use the shared time journal repository'
    );

    $repository = file_get_contents(__DIR__ . '/../inc/time_journal_repository.php');
    test_assert_true(
        strpos($repository, 'LEFT JOIN employees supervisor') !== false,
        'The remote work query must load supervisor names without per-row queries'
    );
    test_assert_same(0, preg_match('/SELECT\s+\*/i', $service), 'Remote work journal queries must select explicit fields');

    $detailPage = file_get_contents(__DIR__ . '/../time_approvement_user.php');
    test_assert_true(
        strpos($detailPage, 'inc/add_time_journal.php') !== false,
        'The full remote work detail page must use the shared data service'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:get_all_add_work_info_by_user|get_superuser_name_by_id|get_user_name_by_id)\s*\(/', $detailPage),
        'The full remote work detail page must not perform legacy or per-row lookups'
    );
    test_assert_true(
        strpos($detailPage, 'notification-table-scroll notification-table-scroll-full') !== false,
        'The existing remote work detail layout must remain available'
    );

    $employeeTable = file_get_contents(__DIR__ . '/../ajax/get_add_times_table.php');
    test_assert_true(
        strpos($employeeTable, 'inc/add_time_journal.php') !== false,
        'The employee remote work table must use the shared data service'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:SELECT|get_all_add_work_info_by_user|get_superuser_name_by_id|get_user_name_by_id)\b/i', $employeeTable),
        'The employee remote work table must not perform SQL, legacy, or per-row lookups'
    );
    test_assert_true(
        strpos($employeeTable, 'journal-action-button journal-action-button-add') !== false,
        'The existing employee remote work controls must remain available'
    );
    test_assert_true(
        strpos($service, '$includeDeleted') !== false,
        'The shared data service must support hiding deleted employee entries'
    );

    $preview = file_get_contents(__DIR__ . '/../ajax/get_add_times.php');
    test_assert_true(
        strpos($preview, 'inc/add_time_journal.php') !== false,
        'The remote work preview must use the shared data service'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:SELECT|get_all_add_work_info_by_user|get_name_by_userid|get_superuser_name_by_id)\b/i', $preview),
        'The remote work preview must not perform SQL, legacy, or per-row lookups'
    );
    test_assert_true(
        strpos($preview, 'id=\"addTimesTable\"') !== false,
        'The existing remote work preview table must remain available'
    );
    test_assert_true(
        strpos($preview, 'add_addition_time();') !== false && strpos($preview, 'part_time_del(') !== false,
        'The existing remote work preview controls must remain available'
    );
};
