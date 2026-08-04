<?php

require_once __DIR__ . '/date_range.php';

function entrance_adjustment_datetime_is_defined($value)
{
    return is_string($value)
        && $value !== ''
        && $value !== '0000-00-00 00:00:00'
        && strtotime($value) !== false;
}

function build_entrance_adjustment($visitRow, $newInTime)
{
    if (!is_array($visitRow) || !isset($visitRow['in_dt'], $visitRow['state'])) {
        return array('code' => 0);
    }

    $originalInTimestamp = strtotime($visitRow['in_dt']);
    $normalizedTime = normalize_time_value($newInTime);

    if ($originalInTimestamp === false || $normalizedTime === null) {
        return array('code' => 0);
    }

    $newInDT = date('Y-m-d', $originalInTimestamp) . ' ' . $normalizedTime;
    $newInTimestamp = strtotime($newInDT);

    if ($newInTimestamp === false) {
        return array('code' => 0);
    }

    $state = (int)$visitRow['state'];
    $eatStartDT = isset($visitRow['eat_start_dt']) ? $visitRow['eat_start_dt'] : '0000-00-00 00:00:00';
    $eatStopDT = isset($visitRow['eat_stop_dt']) ? $visitRow['eat_stop_dt'] : '0000-00-00 00:00:00';
    $outDT = isset($visitRow['out_dt']) ? $visitRow['out_dt'] : '0000-00-00 00:00:00';

    if ($state === 0) {
        if (entrance_adjustment_datetime_is_defined($outDT) && $newInTimestamp > strtotime($outDT)) {
            return array('code' => -10);
        }

        if (
            entrance_adjustment_datetime_is_defined($eatStartDT)
            && $newInTimestamp > strtotime($eatStartDT)
        ) {
            $offset = $newInTimestamp - strtotime($eatStartDT) + 1;
            $eatStartDT = date('Y-m-d H:i:s', strtotime($eatStartDT) + $offset);

            if (entrance_adjustment_datetime_is_defined($eatStopDT)) {
                $eatStopDT = date('Y-m-d H:i:s', strtotime($eatStopDT) + $offset);

                if (
                    entrance_adjustment_datetime_is_defined($outDT)
                    && strtotime($eatStopDT) > strtotime($outDT)
                ) {
                    $outDT = date('Y-m-d H:i:s', strtotime($eatStopDT) + 1);
                }
            }
        }

        $resultCode = 1;
    } elseif ($state === 4) {
        if (!entrance_adjustment_datetime_is_defined($eatStartDT)) {
            return array('code' => 0);
        }

        if ($newInTimestamp > strtotime($eatStartDT)) {
            return array('code' => -11);
        }

        $resultCode = 2;
    } elseif ($state === 3) {
        if (!entrance_adjustment_datetime_is_defined($eatStartDT)) {
            return array('code' => 0);
        }

        if ($newInTimestamp >= strtotime($eatStartDT)) {
            return array('code' => -12);
        }

        $resultCode = 2;
    } elseif ($state === 2) {
        $resultCode = 3;
    } else {
        return array('code' => 0);
    }

    return array(
        'code' => $resultCode,
        'in_dt' => $newInDT,
        'out_dt' => $outDT,
        'eat_start_dt' => $eatStartDT,
        'eat_stop_dt' => $eatStopDT,
    );
}
