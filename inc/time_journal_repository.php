<?php

function time_journal_query_legacy_add_time_columns($link)
{
    return db_query($link, "SHOW COLUMNS FROM ADD_TIME WHERE Field IN ('STARTDATE', 'STARTTIME', 'STOPTIME')");
}

function time_journal_query_delay_statuses($link, $userId, $currentDate, $depthDays)
{
    return db_query(
        $link,
        "SELECT a.status
         FROM Delays a
         JOIN visiting b ON a.date = CAST(b.in_dt AS DATE) AND a.userID = b.user_id
         WHERE a.userID = ?
           AND b.remoteWorkState = 0
           AND a.date > ADDDATE(?, INTERVAL ? DAY)",
        'isi',
        array((int)$userId, $currentDate, (int)$depthDays)
    );
}

function time_journal_query_pause_intervals($link, $userId, $quarterStartDate, $quarterStopExclusive, $startExpr, $stopExpr)
{
    return db_query(
        $link,
        "SELECT $startExpr AS START_DT_EFFECTIVE
         FROM ADD_TIME a
         WHERE a.USERID = ?
           AND a.PAUSE_MODE = 1
           AND $startExpr >= ?
           AND $startExpr < ?
           AND $startExpr <> '0000-00-00 00:00:00'
           AND $stopExpr <> '0000-00-00 00:00:00'
           AND $stopExpr > $startExpr",
        'iss',
        array((int)$userId, $quarterStartDate, $quarterStopExclusive)
    );
}

function time_journal_query_add_time_statuses($link, $userId, $currentDate, $depthDays, $startExpr, $stopExpr)
{
    return db_query(
        $link,
        "SELECT APPROVED
         FROM ADD_TIME a
         WHERE a.PAUSE_MODE = 0
           AND a.USERID = ?
           AND $stopExpr > ADDDATE(?, INTERVAL ? DAY)
           AND $stopExpr <> '0000-00-00 00:00:00'
           AND $startExpr <> '0000-00-00 00:00:00'
           AND $stopExpr > $startExpr",
        'isi',
        array((int)$userId, $currentDate, (int)$depthDays)
    );
}

function time_journal_query_pending_add_time($link, $supervisorId, $currentDate, $depthDays)
{
    return db_query(
        $link,
        "SELECT * FROM ADD_TIME
         WHERE approved = 0
           AND pause_mode = 0
           AND STOP_DT > ADDDATE(?, INTERVAL ? DAY)
           AND userid IN (
             SELECT USERID FROM GROUPS WHERE SUPERVISORID = ? AND type = 0
           )",
        'sii',
        array($currentDate, (int)$depthDays, (int)$supervisorId)
    );
}

function time_journal_query_pending_delays($link, $supervisorId, $currentDate)
{
    return db_query(
        $link,
        "SELECT *
         FROM Delays a
         JOIN visiting b ON a.date = CAST(b.in_dt AS DATE) AND a.userID = b.user_id
         WHERE a.date > ADDDATE(?, INTERVAL -180 DAY)
           AND a.status = 0
           AND b.remoteWorkState = 0
           AND a.userid IN (
             SELECT c.userid FROM GROUPS c WHERE c.supervisorid = ? AND type = 3
           )",
        'si',
        array($currentDate, (int)$supervisorId)
    );
}

function time_journal_query_add_time_by_alert($link, $userId, $date)
{
    return db_query(
        $link,
        'SELECT 1 FROM ADD_TIME WHERE STARTDATE = ? AND USERID = ? AND BYALERT = 1 LIMIT 1',
        'si',
        array($date, (int)$userId)
    );
}

function time_journal_query_approved_legacy_add_time($link, $userId, $startDate, $stopDate)
{
    return db_query(
        $link,
        'SELECT STARTTIME, STOPTIME FROM ADD_TIME
         WHERE STARTDATE >= ? AND STARTDATE <= ? AND USERID = ? AND APPROVED = 1',
        'ssi',
        array($startDate, $stopDate, (int)$userId)
    );
}

function time_journal_query_delays_for_day($link, $userId, $date)
{
    return db_query(
        $link,
        'SELECT DISTINCT id, supervisorID, explaneDesk, acceptorID, penaltyID, penaltyReply, status
         FROM Delays WHERE date = ? AND userID = ?',
        'si',
        array($date, (int)$userId)
    );
}

function time_journal_query_first_visit_for_day($link, $userId, $date)
{
    return db_query(
        $link,
        'SELECT in_dt FROM visiting
         WHERE user_id = ? AND in_dt >= ? AND in_dt < ADDDATE(?, INTERVAL 1 DAY)
         ORDER BY in_dt ASC LIMIT 1',
        'iss',
        array((int)$userId, $date, $date)
    );
}

function time_journal_query_delays_for_range($link, $userId, $startDate, $stopDate)
{
    return db_query(
        $link,
        'SELECT DISTINCT id, date, supervisorID, explaneDesk, acceptorID, penaltyID, penaltyReply, status
         FROM Delays WHERE date >= ? AND date <= ? AND userID = ? ORDER BY date DESC',
        'ssi',
        array($startDate, $stopDate, (int)$userId)
    );
}

function time_journal_query_legacy_visit_for_day($link, $userId, $date)
{
    return db_query($link, 'SELECT in_time FROM visiting WHERE user_id = ? AND date = ?', 'is', array((int)$userId, $date));
}

function time_journal_query_delay_journal($link, $userId, $currentDate, $depthDays)
{
    return db_query(
        $link,
        "SELECT DISTINCT a.id, a.date, b.in_dt, a.supervisorID, a.explaneDesk, a.acceptorID,
                         a.penaltyID, a.penaltyReply, a.status, b.timeZoneSec
         FROM Delays a
         JOIN visiting b ON a.date = CAST(b.in_dt AS DATE) AND a.userID = b.user_id
         WHERE a.userID = ?
           AND a.date > ADDDATE(?, INTERVAL ? DAY)
           AND b.remoteWorkState = 0
         ORDER BY date DESC",
        'isi',
        array((int)$userId, $currentDate, (int)$depthDays)
    );
}

function time_journal_query_reasons($link)
{
    return db_query($link, 'SELECT DISTINCT ID, DESCRIPTION FROM REASONS WHERE ID > 0');
}

function time_journal_query_add_work_for_period($link, $userId, $startDateTime, $stopDateTime)
{
    return db_query(
        $link,
        "SELECT DISTINCT a.ID, a.START_DT, a.STOP_DT, a.SUIR, a.REASON,
                         b.DESCRIPTION AS REASONDESCRIPTION, a.DESCRIPTION, a.SUPERVISORDESC,
                         a.APPROVED, a.PAUSE_MODE
         FROM ADD_TIME a
         JOIN REASONS b ON a.REASON = b.ID
         WHERE a.START_DT <= ?
           AND a.STOP_DT >= ?
           AND a.USERID = ?
         ORDER BY a.START_DT",
        'ssi',
        array($stopDateTime, $startDateTime, (int)$userId)
    );
}

function time_journal_query_add_work_journal($link, $userId, $pauseMode, $currentDate, $depthDays, $startExpr, $stopExpr)
{
    return db_query(
        $link,
        "SELECT DISTINCT a.ID,
                         $startExpr AS START_DT_EFFECTIVE,
                         $stopExpr AS STOP_DT_EFFECTIVE,
                         a.SUIR, a.REASON, b.DESCRIPTION AS REASONDESCRIPTION, a.DESCRIPTION,
                         a.SUPERVISORDESC, a.APPROVED, a.PAUSE_MODE
         FROM ADD_TIME a
         JOIN REASONS b ON a.REASON = b.ID
         WHERE a.USERID = ?
           AND a.PAUSE_MODE = ?
           AND $startExpr <> '0000-00-00 00:00:00'
           AND $stopExpr <> '0000-00-00 00:00:00'
           AND $stopExpr > $startExpr
           AND $stopExpr > ADDDATE(?, INTERVAL ? DAY)
         ORDER BY START_DT_EFFECTIVE DESC",
        'iisi',
        array((int)$userId, (int)$pauseMode, $currentDate, (int)$depthDays)
    );
}

function time_journal_query_legacy_add_work_range($link, $userId, $startDate, $stopDate)
{
    return db_query(
        $link,
        'SELECT DISTINCT ID, STARTDATE, SUIR, STARTTIME, STOPTIME, REASON, DESCRIPTION,
                         SUPERVISORDESC, APPROVED, PAUSE_MODE
         FROM ADD_TIME
         WHERE STARTDATE >= ? AND STARTDATE <= ? AND USERID = ?
         ORDER BY STARTDATE DESC, STARTTIME DESC',
        'ssi',
        array($startDate, $stopDate, (int)$userId)
    );
}
