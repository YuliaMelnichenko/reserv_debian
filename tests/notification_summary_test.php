<?php

return function () {
    $controllers = array(
        __DIR__ . '/../ajax/get_delay_notification_table.php',
        __DIR__ . '/../ajax/get_pause_notification_table.php',
    );

    foreach ($controllers as $controllerPath) {
        $controller = file_get_contents($controllerPath);
        test_assert_true(
            strpos($controller, 'inc/notification_summary.php') !== false,
            'Notification summary controllers must load the shared service in ' . basename($controllerPath)
        );
        test_assert_same(
            0,
            preg_match('/\b(?:SELECT|db_query|get_delay_notif_counts|get_pause_notif_counts|get_user_name_by_id)\b/i', $controller),
            'Summary controllers must not perform SQL or per-user lookups in ' . basename($controllerPath)
        );
    }

    $delayController = file_get_contents($controllers[0]);
    test_assert_true(
        strpos($delayController, 'id=\"delay_approvement_table_users\"') !== false,
        'The existing delay summary table must remain available'
    );
    test_assert_true(
        strpos($delayController, 'show_delays_by_user(') !== false,
        'The existing delay summary navigation must remain available'
    );

    $pauseController = file_get_contents($controllers[1]);
    test_assert_true(
        strpos($pauseController, 'id=\"pause_approvement_table_users\"') !== false,
        'The existing pause summary table must remain available'
    );
    test_assert_true(
        strpos($pauseController, 'show_pause_by_user(') !== false,
        'The existing pause summary navigation must remain available'
    );

    $pages = array(
        __DIR__ . '/../delay_approvement.php',
        __DIR__ . '/../pause_view.php',
    );

    foreach ($pages as $pagePath) {
        $page = file_get_contents($pagePath);
        test_assert_true(
            strpos($page, 'inc/notification_summary.php') !== false,
            'Full notification pages must load the shared summary service in ' . basename($pagePath)
        );
        test_assert_same(
            0,
            preg_match('/\b(?:SELECT|db_query|get_delay_notif_counts|get_pause_notif_counts|get_user_name_by_id)\b/i', $page),
            'Full notification pages must not perform SQL or per-user lookups in ' . basename($pagePath)
        );
        test_assert_true(
            strpos($page, 'journal-view-button') !== false,
            'The existing full-page navigation controls must remain available in ' . basename($pagePath)
        );
    }

    $delayPage = file_get_contents($pages[0]);
    test_assert_true(
        strpos($delayPage, 'delay_approvement_user.php?mid=') !== false,
        'The full delay page must preserve masked detail links'
    );

    $pausePage = file_get_contents($pages[1]);
    test_assert_true(
        strpos($pausePage, 'pause_view_user.php?mid=') !== false,
        'The full pause page must preserve masked detail links'
    );

    $service = file_get_contents(__DIR__ . '/../inc/notification_summary.php');
    test_assert_true(
        strpos($service, "paramName = 'delay_journal_deep_day'") !== false,
        'The delay summary must use the delay journal depth setting'
    );
    test_assert_true(
        strpos($service, 'COUNT(DISTINCT') !== false,
        'Notification counts must remain stable when group or visit rows are duplicated'
    );
    test_assert_true(
        strpos($service, 'get_current_quarter_date_range') !== false,
        'Pause notification counts must be limited to the current quarter'
    );
    test_assert_true(
        strpos($service, 'AND $stopExpression > $startExpression') !== false,
        'Pause notification counts must reject zero and inverted intervals'
    );
    test_assert_same(0, preg_match('/SELECT\s+\*/i', $service), 'Summary queries must select explicit fields');
};
