<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/delay.php';
require_once __DIR__ . '/calendar.php';

function get_delay_journal_context($link, $userID, $currentDate, $includeDeleted = true)
{
    $userResult = db_query($link, "
        SELECT SURNAME, FIRSTNAME, LASTNAME, defaultStartTime, AllowedDelayMinutes
        FROM employees
        WHERE ID = ?
        LIMIT 1
    ", 'i', array((int)$userID));

    if (!$userResult) {
        return false;
    }

    $user = db_fetch_one($userResult);

    if (!$user) {
        return null;
    }

    list($periodStartDate, $periodStopDate, $periodStopExclusive) =
        get_current_quarter_date_range(false, $currentDate);
    $defaultStartTime = (string)$user['defaultStartTime'];
    $allowedDelay = (int)$user['AllowedDelayMinutes'];

    $delayResult = db_query($link, "
        SELECT
          a.id,
          a.date,
          a.supervisorID,
          a.explaneDesk,
          a.acceptorID,
          a.penaltyID,
          a.penaltyReply,
          a.status,
          (
            SELECT MIN(v.in_dt)
            FROM visiting v
            WHERE v.user_id = a.userID
              AND v.in_dt >= a.date
              AND v.in_dt < ADDDATE(a.date, INTERVAL 1 DAY)
              AND v.remoteWorkState = 0
          ) AS in_dt,
          CONCAT_WS(' ', supervisor.SURNAME, supervisor.FIRSTNAME, supervisor.LASTNAME) AS supervisor_name,
          CONCAT_WS(' ', acceptor.SURNAME, acceptor.FIRSTNAME, acceptor.LASTNAME) AS acceptor_name
        FROM Delays a
        LEFT JOIN employees supervisor ON supervisor.ID = a.supervisorID
        LEFT JOIN employees acceptor ON acceptor.ID = a.acceptorID
        WHERE a.userID = ?
          AND a.date >= ?
          AND a.date < ?
        ORDER BY a.date DESC, a.id DESC
    ", 'iss', array((int)$userID, $periodStartDate, $periodStopExclusive));

    if (!$delayResult) {
        return false;
    }

    $entries = array();

    while ($row = db_fetch_one($delayResult)) {
        $status = (int)$row['status'];

        if (!$includeDeleted && in_array($status, array(99, 100, 101), true)) {
            continue;
        }

        $delay = get_delay_value($row['in_dt'], $defaultStartTime, $allowedDelay);

        if ($delay[0] !== 1) {
            continue;
        }

        $entries[] = array(
            'id' => (int)$row['id'],
            'date' => (string)$row['date'],
            'arrival' => (string)$row['in_dt'],
            'duration' => (int)$delay[1],
            'employee_comment' => strip_tags((string)$row['explaneDesk']),
            'supervisor_id' => (int)$row['supervisorID'],
            'supervisor_name' => trim((string)$row['supervisor_name']),
            'acceptor_id' => (int)$row['acceptorID'],
            'acceptor_name' => trim((string)$row['acceptor_name']),
            'penalty_id' => (int)$row['penaltyID'],
            'decision_comment' => (string)$row['penaltyReply'],
            'status' => $status,
            'agreed' => -1,
        );
    }

    return array(
        'user_name' => trim($user['SURNAME'] . ' ' . $user['FIRSTNAME'] . ' ' . $user['LASTNAME']),
        'default_start_time' => $defaultStartTime,
        'allowed_delay' => $allowedDelay,
        'period_start_date' => $periodStartDate,
        'period_stop_date' => $periodStopDate,
        'entries' => $entries,
    );
}
