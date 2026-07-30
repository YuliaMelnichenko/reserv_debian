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

    $menuCountControllers = array(
        __DIR__ . '/../ajax/get_add_time_notif_count.php',
        __DIR__ . '/../ajax/get_delay_notif_count.php',
    );

    foreach ($menuCountControllers as $controllerPath) {
        $controller = file_get_contents($controllerPath);
        test_assert_true(
            strpos($controller, 'inc/notification_summary.php') !== false,
            'Menu notification counters must load the shared service in ' . basename($controllerPath)
        );
        test_assert_true(
            strpos($controller, 'get_supervisor_notification_counts') !== false,
            'Menu notification counters must use the shared count query in ' . basename($controllerPath)
        );
        test_assert_same(
            0,
            preg_match('/\b(?:SELECT|db_query|get_notification_count|get_delay_notification_count)\b/i', $controller),
            'Menu notification counters must not perform SQL or use legacy helpers in ' . basename($controllerPath)
        );
        test_assert_true(
            strpos($controller, 'echo "<h5 class=\\"biggersmall\\">') !== false,
            'Menu notification counters must preserve their label when the count is zero in ' . basename($controllerPath)
        );
    }

    $navigation = file_get_contents(__DIR__ . '/../navigate.php');
    test_assert_true(
        strpos($navigation, 'get_supervisor_notification_counts') !== false,
        'Navigation must load both notification counters through the shared service'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:get_notification_count|get_delay_notification_count)\s*\(/', $navigation),
        'Navigation must not use legacy notification counters'
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
        strpos($service, 'COUNT(DISTINCT') !== false,
        'Notification counts must remain stable when group or visit rows are duplicated'
    );
    $delaySummaryStart = strpos($service, 'function get_delay_notification_summary');
    $pauseSummaryStart = strpos($service, 'function get_pause_notification_summary');
    $delaySummarySource = substr(
        $service,
        $delaySummaryStart,
        $pauseSummaryStart - $delaySummaryStart
    );
    test_assert_true(
        strpos($delaySummarySource, 'LEFT JOIN visiting visit') !== false
            && strpos($delaySummarySource, 'AND EXISTS (') === false,
        'Delay notification summaries must join visits once instead of running a correlated query per delay'
    );
    test_assert_true(
        strpos($service, 'get_current_quarter_date_range') !== false,
        'Notification summaries must be limited to the current quarter'
    );
    test_assert_true(
        strpos($service, 'AND $stopExpression > $startExpression') !== false,
        'Time notification counts must reject zero and inverted intervals'
    );
    test_assert_true(
        strpos($service, 'AND DATE($stopExpression) = DATE($startExpression)') !== false,
        'Pause notifications must reject intervals spanning multiple days'
    );
    test_assert_true(
        strpos($service, 'function get_add_time_notification_summary') !== false,
        'The shared service must provide the remote-work notification summary'
    );
    $addTimeSummaryStart = strpos($service, 'function get_add_time_notification_summary');
    $addTimeSummarySource = substr($service, $addTimeSummaryStart);
    test_assert_true(
        strpos(
            $addTimeSummarySource,
            "time_journal_add_work_datetime_expressions(\$link, 'add_time')"
        ) !== false,
        'The remote-work summary expressions must use the ADD_TIME join alias'
    );
    test_assert_true(
        strpos($delaySummarySource, 'WITHOUT_COMMENT_COUNT') !== false,
        'Delay notifications must expose records without an employee explanation'
    );
    test_assert_true(
        strpos($delaySummarySource, "TRIM(membership.TYPE) IN ('0', '-1', '3')") !== false
            && strpos($delaySummarySource, 'ORDER BY employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME, employee.ID') !== false,
        'Delay notifications must show every direct subordinate in surname order'
    );
    $pauseSummarySource = substr($service, $pauseSummaryStart);
    test_assert_true(
        strpos($pauseSummarySource, 'ORDER BY employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME, employee.ID') !== false,
        'Pause notifications must use the same surname order as other notification lists'
    );
    test_assert_true(
        strpos($delayController, 'Текущий квартал:') !== false
            && strpos($delayController, 'Без<br>объяснения') !== false,
        'The delay notification table must show its period and missing explanations'
    );
    test_assert_true(
        strpos($service, 'function get_pause_notification_count') !== false,
        'The shared service must provide the personal pause notification counter'
    );
    test_assert_true(
        strpos($service, 'function get_supervisor_notification_counts') !== false,
        'The shared service must provide combined supervisor notification counters'
    );
    test_assert_same(0, preg_match('/SELECT\s+\*/i', $service), 'Summary queries must select explicit fields');
};
