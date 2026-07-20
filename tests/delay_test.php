<?php

require_once __DIR__ . '/../inc/delay.php';

return function () {
    test_assert_same(
        array(0, 0),
        get_delay_value('2026-07-20 08:45:00', '09:00:00', 30),
        'An early arrival must never be marked late'
    );
    test_assert_same(
        array(0, 0),
        get_delay_value('2026-07-20 09:30:00', '09:00:00', 30),
        'An arrival at the allowed boundary must not be marked late'
    );
    test_assert_same(
        array(1, 1),
        get_delay_value('2026-07-20 09:30:01', '09:00:00', 30),
        'An arrival after the allowed boundary must be marked late'
    );
    test_assert_same(
        array(1, 900),
        get_delay_value('2026-07-20 09:45:00', '09:00', 30),
        'A short default time must support the allowed delay'
    );
    test_assert_same(
        array(0, 0),
        get_delay_value('2026-07-21 05:00:00', '09:00:00', 30),
        'An early arrival on the following date must use its own calendar day'
    );
    test_assert_same(
        array(0, 0),
        get_delay_value('0000-00-00 00:00:00', '09:00:00', 30),
        'A damaged arrival must not be marked late'
    );
    test_assert_same(
        array(0, 0),
        get_delay_value('2026-07-20 10:00:00', 'NDF', 30),
        'An employee without a default start time must not be marked late'
    );
};
