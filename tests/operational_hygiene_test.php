<?php

return function () {
    $reportPeriodController = file_get_contents(__DIR__ . '/../ajax/set_report_date_interval.php');
    test_assert_same(
        0,
        preg_match('/\berror_log\s*\(/', $reportPeriodController),
        'Changing a report period must not create debug log noise'
    );

    $workdayService = file_get_contents(__DIR__ . '/../inc/workday_transition_service.php');
    test_assert_same(
        false,
        strpos($workdayService, 'TORI_SWITCH_BLOCK_INSERT_RECENT'),
        'The workday state service must not retain temporary debug logging'
    );
};
