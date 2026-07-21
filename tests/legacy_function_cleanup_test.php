<?php

return function () {
    $funcs = file_get_contents(__DIR__ . '/../funcs.php');
    $removedFunctions = array(
        'get_delay_notif_counts',
        'get_add_time_notif_counts',
        'get_all_delay_info_by_user',
        'get_all_add_work_info_by_user',
    );

    foreach ($removedFunctions as $functionName) {
        test_assert_same(
            0,
            preg_match('/function\s+' . preg_quote($functionName, '/') . '\s*\(/', $funcs),
            'Migrated legacy helper must not be restored: ' . $functionName
        );
    }

    test_assert_true(
        strpos($funcs, 'function get_pause_notif_counts') !== false,
        'The pause AJAX counter must remain until its caller is migrated'
    );
};
