<?php

require_once __DIR__ . '/../inc/report_renderer.php';

return function () {
    $usersInfo = array();
    $usersInfo[0] = array(156, 161);
    $usersInfo[1] = array('Первый сотрудник', 'Второй сотрудник');
    $usersInfo[3] = array('08:00:00', '09:30:00');
    $usersInfo[6] = array(15, 30);
    $usersInfo[7] = array(array('first stats'), array('second stats'));

    $first = get_report_user_context($usersInfo, 0);
    $second = get_report_user_context($usersInfo, 1);

    test_assert_same(156, $first['id'], 'The first report column must use the first employee ID');
    test_assert_same('08:00:00', $first['default_start_time'], 'The first employee must keep their own start time');
    test_assert_same(15, $first['allowed_delay'], 'The first employee must keep their own delay allowance');
    test_assert_same(161, $second['id'], 'The second report column must use the second employee ID');
    test_assert_same('09:30:00', $second['default_start_time'], 'The second employee must keep their own start time');
    test_assert_same(30, $second['allowed_delay'], 'The second employee must keep their own delay allowance');
    test_assert_same(null, get_report_user_context($usersInfo, 2), 'A missing report column must be skipped');

    unset($usersInfo[3], $usersInfo[6]);
    $fallback = get_report_user_context($usersInfo, 0);
    test_assert_same('NDF', $fallback['default_start_time'], 'A missing start time must keep the legacy fallback');
    test_assert_same(0, $fallback['allowed_delay'], 'A missing delay allowance must keep the legacy fallback');
};
