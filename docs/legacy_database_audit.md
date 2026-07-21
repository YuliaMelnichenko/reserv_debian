# Legacy datetime fields audit

## Canonical fields

New and updated code must use the following datetime columns:

- `ADD_TIME.START_DT` and `ADD_TIME.STOP_DT`.
- `visiting.in_dt`, `visiting.out_dt`, `visiting.eat_start_dt`, and `visiting.eat_stop_dt`.

The old split columns are retained only while production data is being checked:

- `ADD_TIME.STARTDATE`, `ADD_TIME.STARTTIME`, and `ADD_TIME.STOPTIME`.
- `visiting.date`, `visiting.in_time`, `visiting.out_time`, `visiting.eat_start`, and `visiting.eat_stop`.

## Completed in code

The following paths now use canonical datetime values:

- work outside the office in reports and statistics;
- accounting-alert lookup for `ADD_TIME`;
- delay arrival lookup for a date range;
- pause completion and the latest completed pause preview;
- pause journal range filtering.

Legacy `ADD_TIME` columns are now read only through `add_time_datetime_sql()` as a temporary fallback for historical rows. The test suite rejects new writes to these columns and rejects direct use outside the compatibility layer.

## Remaining visiting compatibility

The following workflows still depend on old `visiting` columns and must be migrated only after the production data has been audited:

- current entrance list in `funcs.php`;
- short statistics in `funcs.php`;
- manual current-day adjustment in `ajax/adj_in_time.php`;
- current-day visit deletion in `ajax/delete_user_visitiong_info_by_currentDay.php`.

Removing these reads or writes before confirming that every old row has canonical datetime values can change employee reports. They are therefore documented rather than silently removed.

## Deployment procedure

1. Run `sql/legacy_datetime_audit.sql` against a staging copy of the production database. It contains read-only statements.
2. Review rows where a canonical value is missing but a legacy value is present.
3. Back up the database and migrate those rows in a separate, reviewed migration.
4. Test entrance, lunch, departure, pause, offsite-work, overtime, and temporary-report workflows.
5. Run the audit again and confirm that no required canonical values are missing.
6. Only then remove the compatibility expressions and old columns in a later release.
