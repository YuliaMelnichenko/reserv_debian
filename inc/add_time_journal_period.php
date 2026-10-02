<?php

require_once __DIR__ . '/journal_period.php';

function get_add_time_journal_period($mode, $manualStartDate = null, $manualStopDate = null, $referenceDate = null)
{
    return get_journal_period($mode, $manualStartDate, $manualStopDate, $referenceDate);
}

function get_add_time_journal_period_from_session($referenceDate = null)
{
    return get_journal_period_from_session('add_time_journal_period', $referenceDate);
}
