<?php

return function () {
    $layoutScript = file_get_contents(__DIR__ . '/../js/tory.js');
    $layoutStyles = file_get_contents(__DIR__ . '/../style/main.css');
    $staffLeavesScript = file_get_contents(__DIR__ . '/../js/staff-leaves.js');

    test_assert_true(
        strpos($layoutScript, 'function fit_notification_scroll') !== false
            && strpos($layoutScript, 'get_vertical_scrollbar_width') !== false,
        'Notification tables must use measured adaptive scroll frames'
    );
    test_assert_true(
        strpos($layoutScript, 'get_notification_nav_cell') !== false
            && strpos($layoutScript, 'childRect.bottom') !== false,
        'Notification height must follow the actual navigation content'
    );
    test_assert_same(
        0,
        preg_match('/fit_notification_table\([^;]+,\s*(?:550|1020|1095)\b/', $layoutScript),
        'Notification layout must not restore fixed panel widths'
    );
    test_assert_true(
        strpos($layoutStyles, 'padding-right: 10px;') !== false
            && strpos($layoutStyles, '.notification-detail-accounting-toolbar') !== false,
        'Notification content and detail toolbars must preserve the shared spacing'
    );
    test_assert_true(
        strpos($staffLeavesScript, 'function fitStaffLeavesLayout') !== false
            && strpos($staffLeavesScript, 'needsVerticalScroll') !== false,
        'Staff leave controls must align to the real table or scrollbar edge'
    );

    $dynamicDetailControllers = array(
        __DIR__ . '/../ajax/get_add_times_by_user.php',
        __DIR__ . '/../ajax/get_delays_by_user.php',
        __DIR__ . '/../ajax/get_pauses_by_user.php',
    );

    foreach ($dynamicDetailControllers as $controllerPath) {
        $controller = file_get_contents($controllerPath);
        test_assert_true(
            strpos($controller, 'notification-table-scroll') !== false,
            'Dynamic notification details must expose a scroll frame in ' . basename($controllerPath)
        );
    }
};
