<?php

require_once __DIR__ . '/../inc/pause_service.php';

return function () {
    test_assert_same(
        array('status' => 'success'),
        time_pause_result('success'),
        'A successful pause transition must preserve the text response contract'
    );
    test_assert_same(
        array('status' => 'error', 'message' => 'Ошибка'),
        time_pause_result('error', 'Ошибка'),
        'A rejected pause transition must preserve its message'
    );

    $invalidStart = start_sport_time_pause(null, 0, 0, '2026-07-21', '2026-07-21 10:00:00', '');
    test_assert_same('error', $invalidStart['status'], 'A sport pause requires an active workday');

    $invalidFinish = finish_time_pause(null, 0, 0, 0, '2026-07-21 10:00:00');
    test_assert_same('error', $invalidFinish['status'], 'Finishing a pause requires valid record identifiers');

    $controllers = array(
        __DIR__ . '/../ajax/set_pause_sport.php',
        __DIR__ . '/../ajax/resume_from_pause.php',
        __DIR__ . '/../ajax/finalize_pause.php',
    );

    foreach ($controllers as $controllerPath) {
        $controller = file_get_contents($controllerPath);
        test_assert_true(
            strpos($controller, 'inc/pause_service.php') !== false,
            'Pause controller must load the shared service in ' . basename($controllerPath)
        );
        test_assert_same(
            0,
            preg_match('/\b(?:UPDATE\s+visiting|INSERT\s+INTO\s+ADD_TIME|UPDATE\s+ADD_TIME)\b/i', $controller),
            'Pause writes must stay in the shared service in ' . basename($controllerPath)
        );
    }

    $service = file_get_contents(__DIR__ . '/../inc/pause_service.php');
    test_assert_true(strpos($service, 'db_transaction_start') !== false, 'Pause transitions must use transactions');
    test_assert_true(strpos($service, 'FOR UPDATE') !== false, 'Pause transitions must lock mutable records');
};
