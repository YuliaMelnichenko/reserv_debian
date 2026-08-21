<?php

require_once __DIR__ . '/add_time_journal.php';
require_once __DIR__ . '/delay_journal.php';

function notification_detail_load_add_time_context($link, $userID, $currentDateTime)
{
    $journal = get_add_time_journal_context($link, $userID, $currentDateTime);

    if (!is_array($journal)) {
        return $journal;
    }

    return array(
        'user_name' => $journal['user_name'],
        'entries' => $journal['entries'],
        'period_label' => format_period_label($journal['period_start_date'], $journal['period_stop_date']),
    );
}

function notification_detail_load_delay_context($link, $userID, $currentDate)
{
    $journal = get_delay_journal_context($link, $userID, $currentDate);

    if (!is_array($journal)) {
        return $journal;
    }

    return array(
        'user_name' => $journal['user_name'],
        'entries' => $journal['entries'],
        'period_label' => format_date_range_label($journal['period_start_date'], $journal['period_stop_date']),
    );
}
