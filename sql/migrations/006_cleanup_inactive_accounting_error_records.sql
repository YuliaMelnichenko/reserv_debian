-- One-time cleanup: inactive employees must not retain accounting-error reminders.
-- Business data, attendance, offsite work, and staff-leave records are not changed.
DELETE error_entry
FROM accounting_errors error_entry
INNER JOIN employees employee ON employee.ID = error_entry.USERID
WHERE employee.RELEVANCE = 0;

DELETE trip
FROM business_trip_missing_data trip
INNER JOIN employees employee ON employee.ID = trip.USERID
WHERE employee.RELEVANCE = 0;
