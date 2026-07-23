<?php

require_once __DIR__ . '/../funcs.php';

return function () {
    $funcs = file_get_contents(__DIR__ . '/../funcs.php');
    $funcsRep = file_get_contents(__DIR__ . '/../funcs_rep.php');
    $employeeDirectory = file_get_contents(__DIR__ . '/../inc/employee_directory.php');
    $workCalendar = file_get_contents(__DIR__ . '/../inc/work_calendar.php');
    $reportStatistics = file_get_contents(__DIR__ . '/../inc/report_statistics.php');
    $reportPresentation = file_get_contents(__DIR__ . '/../inc/report_presentation.php');

    test_assert_true(
        strpos($funcs, "inc/employee_directory.php") !== false,
        'funcs.php must load the employee directory module'
    );
    test_assert_true(
        strpos($funcs, "inc/work_calendar.php") !== false,
        'funcs.php must load the work calendar module'
    );
    test_assert_true(
        strpos($funcs, "inc/report_statistics.php") !== false,
        'funcs.php must load the report statistics module'
    );
    test_assert_true(
        strpos($funcs, "inc/report_presentation.php") !== false,
        'funcs.php must load the report presentation module'
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

    $statisticsFunctions = array(
        'get_penalties',
        'get_current_day_duration_sec',
        'get_stat_by_range',
        'get_stat_set_by_range_full_ex',
    );

    $presentationFunctions = array(
        'represent_is_time_defined',
        'get_range_by_times_pair',
        'colored_result',
        'colored_result_partial',
        'get_cell_content_by_stat',
        'redmine_represent',
        'get_results_cell_content_by_stat',
    );

    foreach ($statisticsFunctions as $functionName) {
        $definitionPattern = '/function\s+' . preg_quote($functionName, '/') . '\s*\(/';
        test_assert_same(0, preg_match($definitionPattern, $funcs), 'Report statistic must stay out of funcs.php: ' . $functionName);
        test_assert_same(0, preg_match($definitionPattern, $funcsRep), 'Report statistic must stay out of funcs_rep.php: ' . $functionName);
        test_assert_same(1, preg_match($definitionPattern, $reportStatistics), 'Report statistic must stay in its module: ' . $functionName);
    }

    foreach ($presentationFunctions as $functionName) {
        $definitionPattern = '/function\s+' . preg_quote($functionName, '/') . '\s*\(/';
        test_assert_same(0, preg_match($definitionPattern, $funcs), 'Report presenter must stay out of funcs.php: ' . $functionName);
        test_assert_same(1, preg_match($definitionPattern, $reportPresentation), 'Report presenter must stay in its module: ' . $functionName);
    }

    test_assert_true(
        strpos($funcsRep, "inc/report_renderer.php") !== false,
        'funcs_rep.php must remain a compatibility loader for the report renderer'
    );

    test_assert_same(
        array(1 => '2026-07-01', 2 => '2026-07-02', 3 => '2026-07-03'),
        get_days_range('2026-07-01', '2026-07-03'),
        'Extracted calendar helpers must preserve their one-based date range contract'
    );
    test_assert_same('2026-07-03', max_date(array(1 => '2026-07-01', 2 => '2026-07-03')), 'Extracted max_date must remain available');
};
