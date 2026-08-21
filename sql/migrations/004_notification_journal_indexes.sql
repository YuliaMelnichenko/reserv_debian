-- Improves lookup of direct subordinates in notification lists and time journals.
-- This migration changes indexes only; it does not modify business data.
ALTER TABLE `GROUPS`
    ADD INDEX idx_groups_supervisor_user_type (SUPERVISORID, USERID, TYPE);

ALTER TABLE ADD_TIME
    ADD INDEX idx_add_time_user_pause_start (USERID, PAUSE_MODE, START_DT);
