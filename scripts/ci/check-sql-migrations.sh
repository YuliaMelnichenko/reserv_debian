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

php scripts/db/migrate.php --check

TORI_MIGRATION_DB_HOST="$TORI_TEST_DB_HOST" \
TORI_MIGRATION_DB_PORT="$TORI_TEST_DB_PORT" \
TORI_MIGRATION_DB_NAME="$TORI_TEST_DB_NAME" \
TORI_MIGRATION_DB_USER="$TORI_TEST_DB_USER" \
TORI_MIGRATION_DB_PASS="$TORI_TEST_DB_PASS" \
php scripts/db/migrate.php --apply --confirm="$TORI_TEST_DB_NAME"

echo 'All SQL migrations were applied to the temporary test database.'
