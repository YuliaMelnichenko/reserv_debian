-- Read-only audit for the transition from split date/time columns to DATETIME columns.
-- The session variables and prepared SELECT statements below do not change data.
-- Run this against a staging copy before preparing any data migration.

SHOW COLUMNS FROM ADD_TIME
WHERE Field IN ('STARTDATE', 'STARTTIME', 'STOPTIME', 'START_DT', 'STOP_DT');

SELECT COUNT(*) INTO @add_time_has_legacy_columns
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'ADD_TIME'
  AND COLUMN_NAME IN ('STARTDATE', 'STARTTIME', 'STOPTIME');

SET @audit_sql = IF(
    @add_time_has_legacy_columns = 3,
    'SELECT
        COUNT(*) AS total_rows,
        SUM(CASE WHEN START_DT IS NOT NULL AND START_DT <> ''0000-00-00 00:00:00'' THEN 1 ELSE 0 END) AS rows_with_start_dt,
        SUM(CASE WHEN STOP_DT IS NOT NULL AND STOP_DT <> ''0000-00-00 00:00:00'' THEN 1 ELSE 0 END) AS rows_with_stop_dt,
        SUM(CASE
            WHEN (START_DT IS NULL OR START_DT = ''0000-00-00 00:00:00'')
                 AND STARTDATE IS NOT NULL AND STARTDATE <> ''0000-00-00''
                 AND STARTTIME IS NOT NULL AND STARTTIME <> ''00:00:00''
            THEN 1 ELSE 0
        END) AS legacy_start_only,
        SUM(CASE
            WHEN (STOP_DT IS NULL OR STOP_DT = ''0000-00-00 00:00:00'')
                 AND STARTDATE IS NOT NULL AND STARTDATE <> ''0000-00-00''
                 AND STOPTIME IS NOT NULL AND STOPTIME <> ''00:00:00''
            THEN 1 ELSE 0
        END) AS legacy_stop_only
     FROM ADD_TIME',
    'SELECT
        ''ADD_TIME: старые колонки STARTDATE, STARTTIME и STOPTIME отсутствуют; сравнение не требуется.'' AS audit_note,
        COUNT(*) AS total_rows,
        SUM(CASE WHEN START_DT <> ''0000-00-00 00:00:00'' THEN 1 ELSE 0 END) AS rows_with_start_dt,
        SUM(CASE WHEN STOP_DT <> ''0000-00-00 00:00:00'' THEN 1 ELSE 0 END) AS rows_with_stop_dt,
        0 AS legacy_start_only,
        0 AS legacy_stop_only
     FROM ADD_TIME'
);
PREPARE legacy_datetime_audit_statement FROM @audit_sql;
EXECUTE legacy_datetime_audit_statement;
DEALLOCATE PREPARE legacy_datetime_audit_statement;

SET @audit_sql = IF(
    @add_time_has_legacy_columns = 3,
    'SELECT ID, USERID, STARTDATE, STARTTIME, STOPTIME, START_DT, STOP_DT
     FROM ADD_TIME
     WHERE ((START_DT IS NULL OR START_DT = ''0000-00-00 00:00:00'')
            AND STARTDATE IS NOT NULL AND STARTDATE <> ''0000-00-00''
            AND STARTTIME IS NOT NULL AND STARTTIME <> ''00:00:00'')
        OR ((STOP_DT IS NULL OR STOP_DT = ''0000-00-00 00:00:00'')
            AND STARTDATE IS NOT NULL AND STARTDATE <> ''0000-00-00''
            AND STOPTIME IS NOT NULL AND STOPTIME <> ''00:00:00'')
     ORDER BY ID
     LIMIT 200',
    'SELECT ''ADD_TIME: список строк со старыми значениями пропущен, так как старых колонок нет.'' AS audit_note'
);
PREPARE legacy_datetime_audit_statement FROM @audit_sql;
EXECUTE legacy_datetime_audit_statement;
DEALLOCATE PREPARE legacy_datetime_audit_statement;

SHOW COLUMNS FROM visiting
WHERE Field IN (
    'date', 'in_time', 'out_time', 'eat_start', 'eat_stop',
    'in_dt', 'out_dt', 'eat_start_dt', 'eat_stop_dt'
);

SELECT COUNT(*) INTO @visiting_has_legacy_columns
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'visiting'
  AND COLUMN_NAME IN ('date', 'in_time', 'out_time', 'eat_start', 'eat_stop');

SET @audit_sql = IF(
    @visiting_has_legacy_columns = 5,
    'SELECT
        COUNT(*) AS total_rows,
        SUM(CASE WHEN in_dt IS NOT NULL AND in_dt <> ''0000-00-00 00:00:00'' THEN 1 ELSE 0 END) AS rows_with_in_dt,
        SUM(CASE
            WHEN (in_dt IS NULL OR in_dt = ''0000-00-00 00:00:00'')
                 AND date IS NOT NULL AND date <> ''0000-00-00''
                 AND in_time IS NOT NULL AND in_time <> ''00:00:00''
            THEN 1 ELSE 0
        END) AS legacy_arrival_only,
        SUM(CASE
            WHEN (out_dt IS NULL OR out_dt = ''0000-00-00 00:00:00'')
                 AND date IS NOT NULL AND date <> ''0000-00-00''
                 AND out_time IS NOT NULL AND out_time <> ''00:00:00''
            THEN 1 ELSE 0
        END) AS legacy_departure_only
     FROM visiting',
    'SELECT
        ''visiting: старые раздельные колонки отсутствуют; сравнение не требуется.'' AS audit_note,
        COUNT(*) AS total_rows,
        SUM(CASE WHEN in_dt <> ''0000-00-00 00:00:00'' THEN 1 ELSE 0 END) AS rows_with_in_dt,
        0 AS legacy_arrival_only,
        0 AS legacy_departure_only
     FROM visiting'
);
PREPARE legacy_datetime_audit_statement FROM @audit_sql;
EXECUTE legacy_datetime_audit_statement;
DEALLOCATE PREPARE legacy_datetime_audit_statement;

SET @audit_sql = IF(
    @visiting_has_legacy_columns = 5,
    'SELECT
        id, user_id, date, in_time, out_time, eat_start, eat_stop,
        in_dt, out_dt, eat_start_dt, eat_stop_dt
     FROM visiting
     WHERE ((in_dt IS NULL OR in_dt = ''0000-00-00 00:00:00'')
            AND date IS NOT NULL AND date <> ''0000-00-00''
            AND in_time IS NOT NULL AND in_time <> ''00:00:00'')
        OR ((out_dt IS NULL OR out_dt = ''0000-00-00 00:00:00'')
            AND date IS NOT NULL AND date <> ''0000-00-00''
            AND out_time IS NOT NULL AND out_time <> ''00:00:00'')
     ORDER BY id
     LIMIT 200',
    'SELECT ''visiting: список строк со старыми значениями пропущен, так как старых колонок нет.'' AS audit_note'
);
PREPARE legacy_datetime_audit_statement FROM @audit_sql;
EXECUTE legacy_datetime_audit_statement;
DEALLOCATE PREPARE legacy_datetime_audit_statement;
