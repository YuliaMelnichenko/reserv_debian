# SQL migrations

Versioned files in this directory are the source of truth for schema changes.
They are applied once and recorded in the `schema_migrations` table with a SHA-256
checksum. Never edit an already applied migration: add the next numbered file.

Check the catalog without connecting to a database:

```bash
php scripts/db/migrate.php --check
```

Preview pending changes on a test or stage database:

```bash
TORI_MIGRATION_DB_HOST=127.0.0.1 \
TORI_MIGRATION_DB_PORT=3306 \
TORI_MIGRATION_DB_NAME=tori_stage \
TORI_MIGRATION_DB_USER=tori_migrator \
TORI_MIGRATION_DB_PASS='<password>' \
php scripts/db/migrate.php --dry-run
```

Apply only after reviewing the preview. The database name must be repeated as a
confirmation, so an accidental command cannot change a database:

```bash
php scripts/db/migrate.php --apply --confirm=tori_stage
```

The older `sql/*.sql` files are retained for compatibility and audit history.
`normalize_invalid_pauses.sql`, `auth_remember_tokens_cleanup.sql`, and
`legacy_datetime_audit.sql` remain manual maintenance scripts: they are not run
by the migration tool or CI because they can change or inspect operational data.

Migration `005_employee_password_hash.sql` adds an empty `PASSWORD_HASH` column
without changing existing MD5 values. After it is applied, each employee who
successfully signs in is transparently upgraded to `password_hash()`; no password
reset is needed. Keep the legacy `passwd` column until a separately approved
final MD5-retirement migration is ready.

## Password hash report

The following read-only script shows aggregate counts of active and archived
accounts: modern hashes, remaining MD5 hashes and records without a supported
hash. It displays no passwords, hashes, logins or employee names and does not
change the database.

```bash
TORI_PASSWORD_AUDIT_DB_HOST=127.0.0.1 \
TORI_PASSWORD_AUDIT_DB_PORT=3306 \
TORI_PASSWORD_AUDIT_DB_NAME=tori_stage \
TORI_PASSWORD_AUDIT_DB_USER=tori_auditor \
TORI_PASSWORD_AUDIT_DB_PASS='<password>' \
php scripts/db/password-hash-report.php
```

For convenience, the script can reuse `TORI_MIGRATION_DB_*` variables when the
dedicated `TORI_PASSWORD_AUDIT_DB_*` names are not set. Give the audit account
only `SELECT` access to the project database.
