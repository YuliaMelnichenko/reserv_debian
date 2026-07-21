<?php

return function () {
    $controller = file_get_contents(__DIR__ . '/../ajax/get_delays_by_user.php');

    test_assert_true(
        strpos($controller, 'inc/delay_journal.php') !== false,
        'The delay journal controller must load the shared data service'
    );
    test_assert_same(
        0,
        preg_match('/\bSELECT\b/i', $controller),
        'SQL must not return to the delay journal controller'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:get_all_delay_info_by_user|get_superuser_name_by_id|get_user_name_by_id)\s*\(/', $controller),
        'The delay journal controller must not perform per-row lookups'
    );
    test_assert_true(
        strpos($controller, 'id=\\"delay_approvement_table\\"') !== false,
        'The existing delay table markup must remain available'
    );

    $service = file_get_contents(__DIR__ . '/../inc/delay_journal.php');
    test_assert_true(
        strpos($service, 'LEFT JOIN employees supervisor') !== false,
        'The delay journal must load supervisor names in its data query'
    );
    test_assert_true(
        strpos($service, 'LEFT JOIN employees acceptor') !== false,
        'The delay journal must load acceptor names in its data query'
    );
    test_assert_same(0, preg_match('/SELECT\s+\*/i', $service), 'Delay journal queries must select explicit fields');
};
