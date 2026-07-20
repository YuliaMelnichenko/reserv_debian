<?php

function get_delay_value($arrivalDateTime, $defaultStartTime, $allowedDelay)
{
    if (
        !is_string($arrivalDateTime)
        || !is_string($defaultStartTime)
        || trim($arrivalDateTime) === ''
        || trim($defaultStartTime) === ''
        || $defaultStartTime === 'NDF'
        || $arrivalDateTime === '0000-00-00 00:00:00'
    ) {
        return array(0, 0);
    }

    $arrivalTimestamp = strtotime($arrivalDateTime);

    if ($arrivalTimestamp === false) {
        return array(0, 0);
    }

    if (strlen($defaultStartTime) === 5) {
        $defaultStartTime .= ':00';
    }

    $arrivalDate = date('Y-m-d', $arrivalTimestamp);
    $startTimestamp = strtotime($arrivalDate . ' ' . $defaultStartTime);

    if ($startTimestamp === false) {
        return array(0, 0);
    }

    $allowedDelay = max(0, (int)$allowedDelay);
    $allowedArrivalTimestamp = $startTimestamp + ($allowedDelay * 60);

    if ($arrivalTimestamp <= $allowedArrivalTimestamp) {
        return array(0, 0);
    }

    return array(1, $arrivalTimestamp - $allowedArrivalTimestamp);
}
