-- Read-only audit for the transition from split date/time columns to DATETIME columns.
-- Run this against a staging copy before preparing any data migration.

SHOW COLUMNS FROM ADD_TIME
WHERE Field IN ('STARTDATE', 'STARTTIME', 'STOPTIME', 'START_DT', 'STOP_DT');

SELECT
    COUNT(*) AS total_rows,
    SUM(CASE WHEN START_DT IS NOT NULL AND START_DT <> '0000-00-00 00:00:00' THEN 1 ELSE 0 END) AS rows_with_start_dt,
    SUM(CASE WHEN STOP_DT IS NOT NULL AND STOP_DT <> '0000-00-00 00:00:00' THEN 1 ELSE 0 END) AS rows_with_stop_dt,
    SUM(CASE
        WHEN (START_DT IS NULL OR START_DT = '0000-00-00 00:00:00')
             AND STARTDATE IS NOT NULL AND STARTDATE <> '0000-00-00'
             AND STARTTIME IS NOT NULL AND STARTTIME <> '00:00:00'
        THEN 1 ELSE 0
    END) AS legacy_start_only,
    SUM(CASE
        WHEN (STOP_DT IS NULL OR STOP_DT = '0000-00-00 00:00:00')
             AND STARTDATE IS NOT NULL AND STARTDATE <> '0000-00-00'
             AND STOPTIME IS NOT NULL AND STOPTIME <> '00:00:00'
        THEN 1 ELSE 0
    END) AS legacy_stop_only
FROM ADD_TIME;

SELECT ID, USERID, STARTDATE, STARTTIME, STOPTIME, START_DT, STOP_DT
FROM ADD_TIME
WHERE ((START_DT IS NULL OR START_DT = '0000-00-00 00:00:00')
       AND STARTDATE IS NOT NULL AND STARTDATE <> '0000-00-00'
       AND STARTTIME IS NOT NULL AND STARTTIME <> '00:00:00')
   OR ((STOP_DT IS NULL OR STOP_DT = '0000-00-00 00:00:00')
       AND STARTDATE IS NOT NULL AND STARTDATE <> '0000-00-00'
       AND STOPTIME IS NOT NULL AND STOPTIME <> '00:00:00')
ORDER BY ID
LIMIT 200;

SHOW COLUMNS FROM visiting
WHERE Field IN (
    'date', 'in_time', 'out_time', 'eat_start', 'eat_stop',
    'in_dt', 'out_dt', 'eat_start_dt', 'eat_stop_dt'
);

SELECT
    COUNT(*) AS total_rows,
    SUM(CASE WHEN in_dt IS NOT NULL AND in_dt <> '0000-00-00 00:00:00' THEN 1 ELSE 0 END) AS rows_with_in_dt,
    SUM(CASE
        WHEN (in_dt IS NULL OR in_dt = '0000-00-00 00:00:00')
             AND date IS NOT NULL AND date <> '0000-00-00'
             AND in_time IS NOT NULL AND in_time <> '00:00:00'
        THEN 1 ELSE 0
    END) AS legacy_arrival_only,
    SUM(CASE
        WHEN (out_dt IS NULL OR out_dt = '0000-00-00 00:00:00')
             AND date IS NOT NULL AND date <> '0000-00-00'
             AND out_time IS NOT NULL AND out_time <> '00:00:00'
        THEN 1 ELSE 0
    END) AS legacy_departure_only
FROM visiting;

SELECT
    id, user_id, date, in_time, out_time, eat_start, eat_stop,
    in_dt, out_dt, eat_start_dt, eat_stop_dt
FROM visiting
WHERE ((in_dt IS NULL OR in_dt = '0000-00-00 00:00:00')
       AND date IS NOT NULL AND date <> '0000-00-00'
       AND in_time IS NOT NULL AND in_time <> '00:00:00')
   OR ((out_dt IS NULL OR out_dt = '0000-00-00 00:00:00')
       AND date IS NOT NULL AND date <> '0000-00-00'
       AND out_time IS NOT NULL AND out_time <> '00:00:00')
ORDER BY id
LIMIT 200;
