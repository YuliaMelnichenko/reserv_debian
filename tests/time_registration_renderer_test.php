<?php

require_once __DIR__ . '/../inc/time_registration_renderer.php';

return function () {
    $arrival = in_time_part('2026-07-21 08:30:00', 0, 0, 440, 176);
    test_assert_true(
        strpos($arrival, 'Время прихода на рабочее место') !== false,
        'The arrival renderer must keep its existing label'
    );
    test_assert_true(
        strpos($arrival, '>08:30:00</h5>') !== false,
        'The arrival renderer must keep the time-only representation'
    );

    $delayedArrival = in_time_part('2026-07-21 10:31:00', 0, 2, 440, 176);
    test_assert_true(
        strpos($delayedArrival, 'onclick="add_expl();"') !== false,
        'A delayed arrival must keep the explanation button'
    );

    $disabledDeparture = change_out_time_disabled('0000-00-00 00:00:00', '10:00:00');
    test_assert_true(
        strpos($disabledDeparture, 'id="add_out_time_disabled" disabled') !== false,
        'Departure must remain disabled while lunch return time is missing'
    );

    $controller = file_get_contents(__DIR__ . '/../ajax/get_time_registration_div.php');
    $panel = file_get_contents(__DIR__ . '/../inc/time_registration_panel.php');
    test_assert_true(
        strpos($controller, 'inc/time_registration_panel.php') !== false,
        'The time registration controller must load the extracted panel'
    );
    test_assert_true(
        strpos($panel, 'time_registration_renderer.php') !== false,
        'The extracted panel must load the shared renderer'
    );
    test_assert_same(
        0,
        preg_match('/function\s+(?:in_time_part|pure_work_day_duration_part|delay_part)\s*\(/', $controller),
        'Rendering helpers must not return to the AJAX controller'
    );

    $indexScript = file_get_contents(__DIR__ . '/../js/index-page.js');
    test_assert_true(
        strpos($indexScript, 'switch_day_state(0, function()') !== false
            && strpos($indexScript, 'window.location.reload();') !== false,
        'Rolling back the time-registration state must refresh the page after a successful update'
    );
    test_assert_true(
        preg_match('/function\\s+reg_eat_stop\\s*\\(\\)\\s*\\{.*?get_time_registration_div_content\\s*\\(\\).*?\\}/s', $indexScript) === 1
            && preg_match('/function\\s+reg_eat_stop\\s*\\(\\)\\s*\\{.*?location\\.reload\\s*\\(/s', $indexScript) === 0,
        'Returning from lunch must refresh only the time-registration panel, not reload the whole current-day page'
    );

    $lunchOverlay = file_get_contents(__DIR__ . '/../ajax/set_lunch.php');
    $pauseOverlay = file_get_contents(__DIR__ . '/../ajax/get_pause_stop_content.php');
    test_assert_true(
        strpos($lunchOverlay, 'lunch-pause-dialog') !== false
            && strpos($pauseOverlay, 'pause-status-dialog') !== false,
        'Lunch and pause status overlays must use their compact dialog styles'
    );
};
