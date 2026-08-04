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
    test_assert_same(
        0,
        preg_match('/\bget_user_name_by_id\s*\(/', $page),
        'The accounting-error list must reuse sorted employee names without per-row lookups'
    );
};
