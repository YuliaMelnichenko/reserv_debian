#!/usr/bin/env bash
set -euo pipefail

required_variables=(
  TORI_TEST_DB_HOST
  TORI_TEST_DB_PORT
  TORI_TEST_DB_NAME
  TORI_TEST_DB_USER
  TORI_TEST_DB_PASS
)

for variable_name in "${required_variables[@]}"; do
  if [[ -z "${!variable_name:-}" ]]; then
    echo "Missing test database setting: ${variable_name}" >&2
    exit 1
  fi
done

migration_files=(
  'sql/accounting_errors.sql'
  'sql/auth_remember_tokens.sql'
  'sql/business_trip_missing_data.sql'
  'sql/normalize_invalid_pauses.sql'
  'sql/auth_remember_tokens_cleanup.sql'
)

for sql_file in sql/*.sql; do
  case "$sql_file" in
    sql/accounting_errors.sql|sql/auth_remember_tokens.sql|sql/business_trip_missing_data.sql|sql/normalize_invalid_pauses.sql|sql/auth_remember_tokens_cleanup.sql|sql/legacy_datetime_audit.sql)
      ;;
    *)
      echo "Classify the new SQL file in scripts/ci/check-sql-migrations.sh: ${sql_file}" >&2
      exit 1
      ;;
  esac
done

for sql_file in "${migration_files[@]}"; do
  echo "Checking migration: ${sql_file}"
  MYSQL_PWD="$TORI_TEST_DB_PASS" mysql \
    --protocol=TCP \
    --host="$TORI_TEST_DB_HOST" \
    --port="$TORI_TEST_DB_PORT" \
    --user="$TORI_TEST_DB_USER" \
    "$TORI_TEST_DB_NAME" < "$sql_file"
done

echo 'All SQL migrations were applied to the temporary test database.'
