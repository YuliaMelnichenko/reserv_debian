<?php

function time_journal_query_legacy_add_time_columns($link)
{
    return db_query($link, "SHOW COLUMNS FROM ADD_TIME WHERE Field IN ('STARTDATE', 'STARTTIME', 'STOPTIME')");
}

function add_time_legacy_datetime_columns_exist($link)
{
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    $exists = false;
    $result = time_journal_query_legacy_add_time_columns($link);

    if ($result && mysqli_num_rows($result) === 3) {
        $exists = true;
    }

    return $exists;
}

function add_time_datetime_sql($dateTimeColumn, $dateColumn = null, $timeColumn = null, $link = null)
{
    if ($link === null || $dateColumn === null || $timeColumn === null || !add_time_legacy_datetime_columns_exist($link)) {
        return "CASE
          WHEN $dateTimeColumn IS NOT NULL AND $dateTimeColumn <> '0000-00-00 00:00:00' THEN $dateTimeColumn
          ELSE '0000-00-00 00:00:00'
        END";
    }

    return "CASE
      WHEN $dateTimeColumn IS NOT NULL AND $dateTimeColumn <> '0000-00-00 00:00:00' THEN $dateTimeColumn
      WHEN $dateColumn IS NOT NULL AND $dateColumn <> '0000-00-00'
        AND $timeColumn IS NOT NULL AND $timeColumn <> '' AND $timeColumn <> '00:00:00'
        THEN IF(LOCATE('-', $timeColumn) > 0, $timeColumn, CONCAT($dateColumn, ' ', $timeColumn))
      ELSE '0000-00-00 00:00:00'
    END";
}

function time_journal_add_work_datetime_expressions($link)
{
    return array(
        'start' => add_time_datetime_sql('a.START_DT', 'a.STARTDATE', 'a.STARTTIME', $link),
        'stop' => add_time_datetime_sql('a.STOP_DT', 'a.STARTDATE', 'a.STOPTIME', $link),
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
        'SELECT 1 FROM ADD_TIME
         WHERE START_DT >= ? AND START_DT < ADDDATE(?, INTERVAL 1 DAY)
           AND USERID = ? AND BYALERT = 1 LIMIT 1',
        'ssi',
        array($date, $date, (int)$userId)
    );
}

function time_journal_query_approved_add_time($link, $userId, $startDateTime, $stopDateTime)
{
    return db_query(
        $link,
        'SELECT START_DT, STOP_DT FROM ADD_TIME
         WHERE START_DT < ? AND STOP_DT > ?
           AND USERID = ? AND APPROVED = 1 AND PAUSE_MODE = 0
           AND START_DT <> \'0000-00-00 00:00:00\'
           AND STOP_DT <> \'0000-00-00 00:00:00\'
           AND STOP_DT > START_DT',
        'ssi',
        array($stopDateTime, $startDateTime, (int)$userId)
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
        'SELECT DISTINCT a.id, a.date, a.supervisorID, a.explaneDesk, a.acceptorID,
                         a.penaltyID, a.penaltyReply, a.status,
                         (SELECT MIN(v.in_dt)
                          FROM visiting v
                          WHERE v.user_id = a.userID
                            AND v.in_dt >= a.date
                            AND v.in_dt < ADDDATE(a.date, INTERVAL 1 DAY)) AS in_dt
         FROM Delays a
         WHERE a.date >= ? AND a.date <= ? AND a.userID = ?
         ORDER BY a.date DESC',
        'ssi',
        array($startDate, $stopDate, (int)$userId)
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
                         a.SUPERVISORDESC, a.APPROVED, a.PAUSE_MODE,
                         CONCAT_WS(' ', supervisor.SURNAME, supervisor.FIRSTNAME, supervisor.LASTNAME) AS SUPERVISOR_NAME
         FROM ADD_TIME a
         JOIN REASONS b ON a.REASON = b.ID
         LEFT JOIN employees supervisor ON supervisor.ID = a.SUIR
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

function time_journal_query_pause_journal(
    $link,
    $userId,
    $quarterStartDate,
    $quarterStopExclusive,
    $startExpr,
    $stopExpr
)
{
    return db_query(
        $link,
        "SELECT
           a.ID,
           $startExpr AS START_DT_EFFECTIVE,
           $stopExpr AS STOP_DT_EFFECTIVE,
           a.DESCRIPTION,
           a.SUIR,
           CONCAT_WS(' ', supervisor.SURNAME, supervisor.FIRSTNAME, supervisor.LASTNAME) AS SUPERVISOR_NAME
         FROM ADD_TIME a
         LEFT JOIN employees supervisor ON supervisor.ID = a.SUIR
         WHERE a.USERID = ?
           AND a.PAUSE_MODE = 1
           AND $startExpr <> '0000-00-00 00:00:00'
           AND $stopExpr <> '0000-00-00 00:00:00'
           AND $stopExpr > $startExpr
           AND $startExpr >= ?
           AND $startExpr < ?
         ORDER BY START_DT_EFFECTIVE DESC, a.ID DESC",
        'iss',
        array((int)$userId, $quarterStartDate, $quarterStopExclusive)
    );
}

function time_journal_query_open_pause($link, $userId)
{
    return db_query(
        $link,
        'SELECT ID FROM ADD_TIME
         WHERE USERID = ? AND PAUSE_MODE = 1
           AND START_DT <> \'0000-00-00 00:00:00\'
           AND (STOP_DT IS NULL OR STOP_DT = \'0000-00-00 00:00:00\')
         ORDER BY START_DT DESC LIMIT 1',
        'i',
        array((int)$userId)
    );
}

function time_journal_finish_pause($link, $pauseId, $userId, $stopDateTime)
{
    return db_execute(
        $link,
        'UPDATE ADD_TIME SET STOP_DT = ? WHERE ID = ? AND USERID = ? AND PAUSE_MODE = 1',
        'sii',
        array($stopDateTime, (int)$pauseId, (int)$userId)
    );
}

function time_journal_query_latest_completed_pause($link, $userId, $date)
{
    return db_query(
        $link,
        'SELECT ID, SUIR, START_DT, STOP_DT, DESCRIPTION
         FROM ADD_TIME
         WHERE USERID = ? AND PAUSE_MODE = 1
           AND START_DT >= ? AND START_DT < ADDDATE(?, INTERVAL 1 DAY)
           AND STOP_DT > START_DT
         ORDER BY START_DT DESC LIMIT 1',
        'iss',
        array((int)$userId, $date, $date)
    );
}
