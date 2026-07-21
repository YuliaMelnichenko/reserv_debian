<?php

require_once __DIR__ . '/../funcs.php';

return function () {
    $funcs = file_get_contents(__DIR__ . '/../funcs.php');
    $employeeDirectory = file_get_contents(__DIR__ . '/../inc/employee_directory.php');
    $workCalendar = file_get_contents(__DIR__ . '/../inc/work_calendar.php');

    test_assert_true(
        strpos($funcs, "inc/employee_directory.php") !== false,
        'funcs.php must load the employee directory module'
    );
    test_assert_true(
        strpos($funcs, "inc/work_calendar.php") !== false,
        'funcs.php must load the work calendar module'
    );

    $employeeFunctions = array(
        'get_sv_name_by_userid',
        'get_group_user_info_by_svID_for_report_ex',
        'am_i_superuser',
        'get_user_rate',
        'get_superuser_names_by_user_id',
        'get_superuser_name_by_id',
        'get_user_name_by_id',
        'get_pause_agree_able_superusers_by_userID',
        'get_users_by_superusers_and_type',
        'get_user_defStartTime_and_allowedDelay',
        'get_and_update_start_time_status',
    );

    $calendarFunctions = array(
        'GetHourNormByMonth',
        'get_workdays_holidays_bay_range',
        'get_holidays',
        'get_work_day',
        'get_days_range',
        'get_days_wo_weekends',
        'get_days_wo_holidays',
        'get_days_with_add_workdays',
        'max_date',
        'min_date',
        'get_norm_by_range_sec',
        'get_norm_time_by_current_day_sec',
        'apply_staff_leaves_to_days_norm',
        'get_staff_leave_events_by_days',
        'get_work_dayoff_types_by_range',
    );

    foreach ($employeeFunctions as $functionName) {
        $definitionPattern = '/function\s+' . preg_quote($functionName, '/') . '\s*\(/';
        test_assert_same(0, preg_match($definitionPattern, $funcs), 'Employee helper must stay out of funcs.php: ' . $functionName);
        test_assert_same(1, preg_match($definitionPattern, $employeeDirectory), 'Employee helper must stay in its module: ' . $functionName);
    }

    foreach ($calendarFunctions as $functionName) {
        $definitionPattern = '/function\s+' . preg_quote($functionName, '/') . '\s*\(/';
        test_assert_same(0, preg_match($definitionPattern, $funcs), 'Calendar helper must stay out of funcs.php: ' . $functionName);
        test_assert_same(1, preg_match($definitionPattern, $workCalendar), 'Calendar helper must stay in its module: ' . $functionName);
    }

    test_assert_same(
        array(1 => '2026-07-01', 2 => '2026-07-02', 3 => '2026-07-03'),
        get_days_range('2026-07-01', '2026-07-03'),
        'Extracted calendar helpers must preserve their one-based date range contract'
    );
    test_assert_same('2026-07-03', max_date(array(1 => '2026-07-01', 2 => '2026-07-03')), 'Extracted max_date must remain available');
};
