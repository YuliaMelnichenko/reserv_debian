<?php

function get_standard_day_transition_time()
{
    return '00:00:00';
}

function normalize_day_transition_time($dayTransitionTime)
{
    return get_standard_day_transition_time();
}

function datetimestr_to_day_start_stop_DT_ex_str($dateTimeStr, $dayTransitionTime)
{
    $dayTransitionTime = normalize_day_transition_time($dayTransitionTime);
    $currentTimestamp = strtotime($dateTimeStr);

    if ($currentTimestamp === false) {
        $currentTimestamp = time();
    }

    $currentDate = date('Y-m-d', $currentTimestamp);
    $startTimestamp = strtotime($currentDate . ' ' . $dayTransitionTime);

    if ($startTimestamp === false) {
        $startTimestamp = strtotime($currentDate . ' 00:00:00');
    }

    $stopTimestamp = strtotime('+1 day', $startTimestamp) - 1;

    return array(
        date('Y-m-d H:i:s', $startTimestamp),
        date('Y-m-d H:i:s', $stopTimestamp),
    );
}

function datetimestr_to_day_start_stop_DT_ex_str_idx($dateTimeStr, $dayTransitionTime)
{
    return datetimestr_to_day_start_stop_DT_ex_str($dateTimeStr, $dayTransitionTime);
}
