<?php

return function () {
    $funcs = file_get_contents(__DIR__ . '/../funcs.php');
    $removedFunctions = array(
        'get_delay_notif_counts',
        'get_pause_notif_counts',
        'get_add_time_notif_counts',
        'get_all_delay_info_by_user',
        'get_all_add_work_info_by_user',
        'get_name_by_userid',
        'get_notification_count',
        'get_delay_notification_count',
        'get_dbsetup_param',
    );

    foreach ($removedFunctions as $functionName) {
        test_assert_same(
            0,
            preg_match('/function\s+' . preg_quote($functionName, '/') . '\s*\(/', $funcs),
            'Migrated legacy helper must not be restored: ' . $functionName
        );
    }

    $repository = file_get_contents(__DIR__ . '/../inc/time_journal_repository.php');
    $removedRepositoryFunctions = array(
        'time_journal_query_delay_statuses',
        'time_journal_query_add_time_statuses',
        'time_journal_query_delay_journal',
        'time_journal_finish_pause',
        'time_journal_query_pending_add_time',
        'time_journal_query_pending_delays',
    );

    foreach ($removedRepositoryFunctions as $functionName) {
        test_assert_same(
            0,
            preg_match('/function\s+' . preg_quote($functionName, '/') . '\s*\(/', $repository),
            'Unused repository helper must not be restored: ' . $functionName
        );
    }
};
