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

    $pauseCountController = file_get_contents(__DIR__ . '/../ajax/get_pause_notif_count.php');
    test_assert_true(
        strpos($pauseCountController, 'inc/notification_summary.php') !== false,
        'The pause notification counter must load the shared summary service'
    );
    test_assert_true(
        strpos($pauseCountController, '$notifCountStr = "";') !== false,
        'The pause notification counter must initialize its empty state'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:SELECT|db_query|get_pause_notif_counts)\b/i', $pauseCountController),
        'The pause notification counter must not perform SQL or use the legacy helper'
    );

    $pages = array(
        __DIR__ . '/../delay_approvement.php',
        __DIR__ . '/../pause_view.php',
        __DIR__ . '/../time_approvement.php',
    );

    foreach ($pages as $pagePath) {
        $page = file_get_contents($pagePath);
        test_assert_true(
            strpos($page, 'inc/notification_summary.php') !== false,
            'Full notification pages must load the shared summary service in ' . basename($pagePath)
        );
        test_assert_same(
            0,
            preg_match('/\b(?:SELECT|db_query|get_delay_notif_counts|get_pause_notif_counts|get_add_time_notif_counts|get_user_name_by_id)\b/i', $page),
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

    $addTimePage = file_get_contents($pages[2]);
    test_assert_true(
        strpos($addTimePage, 'time_approvement_user.php?mid=') !== false,
        'The full remote-work page must preserve masked detail links'
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
        'Time notification counts must reject zero and inverted intervals'
    );
    test_assert_true(
        strpos($service, "paramName = 'add_time_journal_deep_day'") !== false,
        'The remote-work summary must use the add-time journal depth setting'
    );
    test_assert_true(
        strpos($service, 'function get_add_time_notification_summary') !== false,
        'The shared service must provide the remote-work notification summary'
    );
    test_assert_true(
        strpos($service, 'function get_pause_notification_count') !== false,
        'The shared service must provide the personal pause notification counter'
    );
    test_assert_same(0, preg_match('/SELECT\s+\*/i', $service), 'Summary queries must select explicit fields');
};
