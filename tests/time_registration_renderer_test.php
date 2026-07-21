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
    test_assert_true(
        strpos($controller, 'inc/time_registration_renderer.php') !== false,
        'The time registration controller must load the shared renderer'
    );
    test_assert_same(
        0,
        preg_match('/function\s+(?:in_time_part|pure_work_day_duration_part|delay_part)\s*\(/', $controller),
        'Rendering helpers must not return to the AJAX controller'
    );
};
