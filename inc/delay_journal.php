<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/delay.php';

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

    $user = mysqli_fetch_assoc($userResult);

    if (!$user) {
        return null;
    }

    $depthResult = db_query($link, "
        SELECT valueInt
        FROM DBSETUP
        WHERE paramName = 'delay_journal_deep_day'
        LIMIT 1
    ");

    if (!$depthResult) {
        return false;
    }

    $depthRow = mysqli_fetch_assoc($depthResult);
    $depthDays = $depthRow ? abs((int)$depthRow['valueInt']) : 180;
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
          AND a.date > ADDDATE(?, INTERVAL ? DAY)
        ORDER BY a.date DESC, a.id DESC
    ", 'isi', array((int)$userID, $currentDate, -$depthDays));

    if (!$delayResult) {
        return false;
    }

    $entries = array();

    while ($row = mysqli_fetch_assoc($delayResult)) {
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
        'entries' => $entries,
    );
}
