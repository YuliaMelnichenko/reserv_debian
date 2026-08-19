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
    test_assert_same(
        array('status' => 'forbidden', 'message' => 'FORBIDDEN_SUPERVISOR'),
        time_pause_result('forbidden', 'FORBIDDEN_SUPERVISOR'),
        'A forged supervisor must preserve the forbidden response contract'
    );

    $invalidStart = start_sport_time_pause(null, 0, 0, '2026-07-21', '2026-07-21 10:00:00', '');
    test_assert_same('error', $invalidStart['status'], 'A sport pause requires an active workday');

    $invalidRegularStart = start_time_pause(null, 1, 1, 0, '2026-07-21', '2026-07-21 10:00:00', '');
    test_assert_same('error', $invalidRegularStart['status'], 'A regular pause requires a valid supervisor');

    $invalidFinish = finish_time_pause(null, 0, 0, 0, '2026-07-21 10:00:00');
    test_assert_same('error', $invalidFinish['status'], 'Finishing a pause requires valid record identifiers');

    $controllers = array(
        __DIR__ . '/../ajax/set_pause_sport.php',
        __DIR__ . '/../ajax/set_pause.php',
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
    test_assert_true(
        strpos($service, 'FROM `GROUPS`') !== false,
        'Pause supervisor checks must quote the reserved GROUPS table for MySQL compatibility'
    );

    $pauseStateEndpoint = file_get_contents(__DIR__ . '/../ajax/is_there_pause.php');
    $pauseOverlayEndpoint = file_get_contents(__DIR__ . '/../ajax/get_pause_stop_content.php');
    $repository = file_get_contents(__DIR__ . '/../inc/time_journal_repository.php');
    test_assert_true(
        strpos($pauseStateEndpoint, 'time_journal_query_open_pause') !== false
            && strpos($pauseStateEndpoint, 'take_pause') === false,
        'Pause state must be derived from an actual open pause, not a stale visit flag'
    );
    test_assert_true(
        strpos($pauseOverlayEndpoint, 'time_journal_query_open_pause_details') !== false
            && strpos($repository, 'function time_journal_query_open_pause_details') !== false,
        'The pause overlay must use the same open-pause criteria as the state check'
    );
    test_assert_true(
        strpos($pauseStateEndpoint, '$periodStart') !== false
            && strpos($pauseOverlayEndpoint, '$periodStop') !== false
            && strpos($repository, 'DATE($stopExpr) = DATE($startExpr)') !== false,
        'Open pauses and pause reports must be limited to the day where the pause began'
    );

    $scripts = file_get_contents(__DIR__ . '/../js/tory.js');
    test_assert_true(
        strpos($scripts, "pauseHtml === '0'") !== false
            && strpos($scripts, "pauseHtml.indexOf('id=\"pauseFullScreen\"')") !== false,
        'A non-overlay AJAX response must never replace the full page body'
    );
};
