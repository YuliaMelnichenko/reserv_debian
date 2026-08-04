<?php

require_once __DIR__ . '/../inc/employee_registration.php';
require_once __DIR__ . '/../inc/index_page_service.php';

return function () {
    $projectRoot = realpath(__DIR__ . '/..');
    $controllerModules = array(
        'ajax/get_time_registration_div.php' => 'inc/time_registration_panel.php',
        'ajax/switch_day_state.php' => 'inc/workday_transition_service.php',
    );

    foreach ($controllerModules as $controllerPath => $modulePath) {
        $source = file_get_contents($projectRoot . '/' . $controllerPath);

        test_assert_true(
            strpos($source, $modulePath) !== false,
            $controllerPath . ' must load ' . $modulePath
        );
        test_assert_true(
            substr_count($source, PHP_EOL) < 50,
            $controllerPath . ' must remain a thin controller'
        );
        test_assert_same(
            0,
            preg_match('/\b(?:SELECT|INSERT|UPDATE|DELETE)\b/i', $source),
            $controllerPath . ' must not contain SQL'
        );
    }

    $pageModules = array(
        'index.php' => 'inc/index_page_service.php',
        'register.php' => 'inc/employee_registration.php',
    );

    foreach ($pageModules as $pagePath => $modulePath) {
        $source = file_get_contents($projectRoot . '/' . $pagePath);

        test_assert_true(
            strpos($source, $modulePath) !== false,
            $pagePath . ' must load ' . $modulePath
        );
    }

    $pageViews = array(
        'index.php' => 'views/index_page.php',
        'staff_leaves.php' => 'views/staff_leaves_page.php',
        'my_report.php' => 'views/my_report_page.php',
        'work_overtime.php' => 'views/work_overtime_page.php',
        'report.php' => 'views/report_page.php',
    );

    foreach ($pageViews as $pagePath => $viewPath) {
        $source = file_get_contents($projectRoot . '/' . $pagePath);

        test_assert_true(
            strpos($source, $viewPath) !== false,
            $pagePath . ' must load its extracted view'
        );
        test_assert_true(
            substr_count($source, PHP_EOL) < 50,
            $pagePath . ' must remain a thin page controller'
        );
    }

    $databaseConsumers = array(
        'index.php',
        'work_overtime.php',
        'register.php',
        'ajax/get_time_registration_div.php',
        'ajax/switch_day_state.php',
        'inc/employee_registration.php',
        'inc/index_page_service.php',
        'inc/pause_service.php',
        'inc/remote_work.php',
        'inc/time_registration_panel.php',
        'inc/time_registration_renderer.php',
        'inc/workday_registration.php',
        'inc/workday_transition_service.php',
    );

    foreach ($databaseConsumers as $relativePath) {
        $source = file_get_contents($projectRoot . '/' . $relativePath);

        test_assert_same(
            0,
            preg_match('/\bmysqli_[a-z_]+\s*\(/i', $source),
            'Direct mysqli calls must stay in the database layer: ' . $relativePath
        );
    }

    $indexSource = file_get_contents($projectRoot . '/index.php');
    test_assert_same(
        0,
        preg_match('/function\s+(?:sort_employee|getHolidayDates|getWorkingDaysUntil|getDayWord|getDaysLeft)\s*\(/', $indexSource),
        'Index helpers must stay in the extracted service'
    );

    $invalid = validate_employee_registration_input(array(
        'r_login' => 'a',
        'r_passwd' => 'one',
        'r_passwd_rep' => 'two',
        'r_surname' => '',
        'r_first_name' => '',
        'r_second_name' => '',
    ));
    test_assert_true(count($invalid['errors']) >= 5, 'Registration validation must report invalid fields');

    $valid = validate_employee_registration_input(array(
        'r_login' => 'employee1',
        'r_passwd' => 'pass123',
        'r_passwd_rep' => 'pass123',
        'r_surname' => 'Test',
        'r_first_name' => 'User',
        'r_second_name' => 'Name',
    ));
    test_assert_same(array(), $valid['errors'], 'Registration validation must accept valid input');

    test_assert_same(
        'день',
        getDayWord(1),
        'Extracted index date helpers must remain available'
    );
    test_assert_same(
        'дня',
        getDayWord(2),
        'Extracted index date helpers must preserve Russian plural forms'
    );
    test_assert_same(
        'дней',
        getDayWord(11),
        'Extracted index date helpers must handle the 11-14 exception'
    );
};
