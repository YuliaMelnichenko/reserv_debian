-- A pause must start and finish on the same calendar day.
-- This preserves the row for audit purposes but makes invalid pauses zero-duration,
-- so they no longer affect reports or notifications.
START TRANSACTION;

UPDATE ADD_TIME
SET STOP_DT = START_DT
WHERE PAUSE_MODE = 1
  AND START_DT IS NOT NULL
  AND START_DT <> '0000-00-00 00:00:00'
  AND (
    STOP_DT IS NULL
    OR STOP_DT = '0000-00-00 00:00:00'
    OR DATE(STOP_DT) <> DATE(START_DT)
  );

UPDATE visiting
SET take_pause = 0
WHERE take_pause = 1
  AND in_dt < CURDATE();

COMMIT;
