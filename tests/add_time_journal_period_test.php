<?php

require_once __DIR__ . '/../inc/add_time_journal_period.php';

return function () {
    test_assert_same(
        array(
            'mode' => 4,
            'start_date' => '2026-07-01',
            'stop_date' => '2026-08-21',
            'stop_exclusive' => '2026-08-22',
        ),
        get_add_time_journal_period(4, null, null, '2026-08-21'),
        'The offsite-work journal must support the current-quarter period'
    );
    test_assert_same(
        array(
            'mode' => 5,
            'start_date' => '2026-04-01',
            'stop_date' => '2026-06-30',
            'stop_exclusive' => '2026-07-01',
        ),
        get_add_time_journal_period(5, null, null, '2026-08-21'),
        'The offsite-work journal must support the previous-quarter period'
    );
    test_assert_same(
        array(
            'mode' => 7,
            'start_date' => '2026-01-15',
            'stop_date' => '2026-02-10',
            'stop_exclusive' => '2026-02-11',
        ),
        get_add_time_journal_period(7, '2026-01-15', '2026-02-10', '2026-08-21'),
        'The offsite-work journal must preserve a manual period'
    );
    test_assert_same(
        null,
        get_add_time_journal_period(7, '2026-02-10', '2026-01-15', '2026-08-21'),
        'The offsite-work journal must reject a reversed manual period'
    );
};
