<?php

return function () {
    $service = file_get_contents(__DIR__ . '/../inc/accounting_errors.php');
    test_assert_true(
        strpos($service, 'function get_accounting_errors_supervised_users') !== false,
        'Accounting-error notifications must load supervised employees with their names'
    );
    test_assert_true(
        strpos($service, 'ORDER BY employee.SURNAME, employee.FIRSTNAME, employee.LASTNAME') !== false,
        'Accounting-error employees must be sorted alphabetically by surname'
    );

    $page = file_get_contents(__DIR__ . '/../accounting_errors_approvement.php');
    test_assert_true(
        strpos($page, '$notificationCount <= 0') !== false,
        'Employees without accounting errors must be omitted from the notification list'
    );
    test_assert_true(
        strpos($page, '$businessTripNotificationCount') !== false,
        'Business-trip reminders must keep employees visible in accounting notifications'
    );
    test_assert_same(
        0,
        preg_match('/\bget_user_name_by_id\s*\(/', $page),
        'The accounting-error list must reuse sorted employee names without per-row lookups'
    );

    test_assert_true(
        strpos($service, 'sync_business_trip_missing_data_for_user') !== false
            && strpos($service, 'business_trip_missing_data') !== false,
        'Missing offsite-work data for business trips must be synchronized separately from regular accounting errors'
    );
    test_assert_true(
        strpos($service, 'function get_accounting_errors_rows') !== false,
        'Accounting-error data loading must stay in the shared service'
    );
    test_assert_true(
        strpos($service, 'function is_accounting_errors_active_user') !== false
            && strpos($service, 'RELEVANCE = 1') !== false,
        'Accounting errors must be created and shown only for active employees'
    );
    test_assert_same(
        0,
        preg_match('/(?<!`)\\b(?:FROM|JOIN)\\s+GROUPS\\b/', $service),
        'Accounting-error queries must quote the reserved GROUPS table name'
    );
    test_assert_true(
        strpos($service, 'get_accounting_errors_supervised_user_ids($link, $supervisorID)') !== false,
        'Supervisor notification counts must synchronize business-trip reminders before rendering'
    );
    test_assert_true(
        strpos($service, 'ae.STATUS IN (0, 1, 3)') !== false,
        'Supervisor accounting-error counter must include every unresolved regular error'
    );

    $navigation = file_get_contents(__DIR__ . '/../navigate.php');
    test_assert_true(
        strpos($navigation, '$syncResult = sync_accounting_errors_for_user(') !== false
            && strpos($navigation, '$accountingErrorsSyncDate') === false,
        'Navigation must refresh the accounting-error indicator on every page visit'
    );

    $employeePage = file_get_contents(__DIR__ . '/../accounting_errors.php');
    $supervisorPage = file_get_contents(__DIR__ . '/../accounting_errors_approvement_user.php');
    test_assert_true(
        strpos($employeePage, 'business_trip_missing_data_table') !== false
            && strpos($employeePage, 'Внести данные о работе вне офиса') !== false,
        'Employees must see a separate actionable business-trip reminder table'
    );
    test_assert_true(
        strpos($supervisorPage, 'business_trip_missing_data_supervisor_table') !== false
            && strpos($supervisorPage, 'Данные вне офиса не внесены') !== false,
        'Supervisors must see business-trip reminders as information without approval actions'
    );
    test_assert_same(
        0,
        preg_match('/\bdb_query\s*\(/', $employeePage)
            + preg_match('/\bdb_query\s*\(/', $supervisorPage),
        'Accounting-error pages must render rows returned by the shared service'
    );

    $styles = file_get_contents(__DIR__ . '/../style/main.css');
    test_assert_true(
        strpos($styles, '#businessTripMissingDataTableScroll') !== false
            && strpos($styles, '#businessTripMissingDataSupervisorTableScroll') !== false
            && strpos($styles, 'margin: 16px 0 10px;') !== false,
        'Business-trip tables must align with accounting-error tables and keep spacing around their heading'
    );

    $staffLeavesPage = file_get_contents(__DIR__ . '/../views/staff_leaves_page.php');
    test_assert_true(
        strpos($staffLeavesPage, 'value=\\"6\\">С начала года') !== false,
        'The staff-leaves archive must offer a year-to-date filter'
    );
};
