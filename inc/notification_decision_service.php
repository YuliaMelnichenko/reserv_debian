<?php

require_once __DIR__ . '/database.php';

function notification_decision_update_add_time($link, $recordID, $supervisorID, $comment, $acceptMode)
{
    if (!in_array((int)$acceptMode, array(-1, 1), true)) {
        return false;
    }

    return db_execute(
        $link,
        'UPDATE ADD_TIME SET SUIR = ?, SUPERVISORDESC = ?, APPROVED = ? WHERE ID = ?',
        'isii',
        array((int)$supervisorID, (string)$comment, (int)$acceptMode, (int)$recordID)
    );
}

function notification_decision_set_add_time_deleted($link, $recordID, $mode)
{
    if (!in_array((int)$mode, array(100, 200), true)) {
        return false;
    }

    $operator = (int)$mode === 100 ? '+' : '-';

    return db_execute(
        $link,
        'UPDATE ADD_TIME SET APPROVED = APPROVED ' . $operator . ' 100 WHERE ID = ?',
        'i',
        array((int)$recordID)
    );
}

function notification_decision_update_delay($link, $recordID, $acceptorID, $comment, $acceptMode)
{
    if (!in_array((int)$acceptMode, array(-1, 1), true)) {
        return false;
    }

    $transaction = db_transaction_start($link);

    if (!$transaction) {
        return false;
    }

    $delayResult = db_query(
        $link,
        'SELECT userID, date, penaltyID FROM Delays WHERE ID = ? LIMIT 1 FOR UPDATE',
        'i',
        array((int)$recordID)
    );
    $delay = db_fetch_one($delayResult);

    if (!$delay) {
        $transaction->rollback();
        return false;
    }

    $employeeID = (int)$delay['userID'];
    $penaltyID = (int)$delay['penaltyID'];
    $newPenaltyID = $penaltyID > 0 ? $penaltyID : -1;

    if ((int)$acceptMode === -1 && $penaltyID <= 0) {
        $lastPenaltyResult = db_query($link, 'SELECT ID FROM Penalty ORDER BY ID DESC LIMIT 1 FOR UPDATE');

        if (!$lastPenaltyResult) {
            $transaction->rollback();
            return false;
        }

        $lastPenalty = db_fetch_one($lastPenaltyResult);
        $newPenaltyID = $lastPenalty ? (int)$lastPenalty['ID'] + 1 : 1;
        $penaltySaved = db_execute(
            $link,
            'INSERT INTO Penalty (date, ID, userID, supervisorID, reason) VALUES (?, ?, ?, ?, ?)',
            'siiis',
            array((string)$delay['date'], $newPenaltyID, $employeeID, (int)$acceptorID, (string)$comment)
        );

        if (!$penaltySaved) {
            $transaction->rollback();
            return false;
        }
    } elseif ((int)$acceptMode === -1) {
        $penaltyUpdated = db_execute(
            $link,
            'UPDATE Penalty SET date = ?, supervisorID = ?, reason = ? WHERE ID = ? AND userID = ?',
            'sisii',
            array((string)$delay['date'], (int)$acceptorID, (string)$comment, $penaltyID, $employeeID)
        );

        if (!$penaltyUpdated) {
            $transaction->rollback();
            return false;
        }
    } elseif ($penaltyID > 0) {
        $penaltyDeleted = db_execute(
            $link,
            'DELETE FROM Penalty WHERE ID = ? AND userID = ?',
            'ii',
            array($penaltyID, $employeeID)
        );

        if (!$penaltyDeleted) {
            $transaction->rollback();
            return false;
        }

        $newPenaltyID = -1;
    }

    $delayUpdated = db_execute(
        $link,
        'UPDATE Delays SET acceptorID = ?, penaltyReply = ?, status = ?, penaltyID = ? WHERE ID = ? AND userID = ?',
        'isiiii',
        array((int)$acceptorID, (string)$comment, (int)$acceptMode, $newPenaltyID, (int)$recordID, $employeeID)
    );

    if (!$delayUpdated) {
        $transaction->rollback();
        return false;
    }

    if (!$transaction->commit()) {
        return false;
    }

    return true;
}

function notification_decision_set_delay_deleted($link, $recordID, $mode)
{
    if (!in_array((int)$mode, array(100, 200), true)) {
        return false;
    }

    $operator = (int)$mode === 100 ? '+' : '-';

    return db_execute(
        $link,
        'UPDATE Delays SET status = status ' . $operator . ' 100 WHERE ID = ?',
        'i',
        array((int)$recordID)
    );
}
