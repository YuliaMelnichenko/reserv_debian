-- Retains legacy MD5 values while adding a modern password_hash() destination.
-- Existing passwords are upgraded only after a successful employee login.
ALTER TABLE employees
    ADD COLUMN PASSWORD_HASH VARCHAR(255) NULL AFTER passwd;
