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
};
