<?php

require_once __DIR__ . '/../inc/gym_schedule.php';

return function () {
    test_assert_same('01 января', format_gym_schedule_date('01 01'), 'January must use the Russian month name');
    test_assert_same('21 июля', format_gym_schedule_date('21 07'), 'July must use the Russian month name');
    test_assert_same('31 декабря', format_gym_schedule_date('31 12'), 'December must use the Russian month name');
    test_assert_same('неизвестно', format_gym_schedule_date('неизвестно'), 'Unknown date text must stay readable');

    $controller = file_get_contents(__DIR__ . '/../ajax/get_pause_sport_table.php');
    test_assert_true(
        strpos($controller, 'inc/gym_schedule.php') !== false,
        'The gym table controller must load the shared data service'
    );
    test_assert_same(
        0,
        preg_match('/\b(?:SELECT|INSERT|UPDATE|DELETE)\b/i', $controller),
        'SQL must not return to the gym table controller'
    );
    test_assert_true(
        strpos($controller, 'class=\\"add_time_sport\\"') !== false,
        'The existing gym table classes must remain in the controller markup'
    );

    $service = file_get_contents(__DIR__ . '/../inc/gym_schedule.php');
    test_assert_same(
        0,
        preg_match('/SELECT\s+\*/i', $service),
        'Gym schedule queries must select only the fields they use'
    );
};
