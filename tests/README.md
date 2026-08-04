# Tests

Run the dependency-free test suite from the project root:

```bash
php8.2 tests/run.php
```

The tests do not connect to MySQL and do not modify application data.

## MySQL integration tests

Create a separate empty database whose name contains `test`, grant a dedicated
user access to it, and run:

```bash
TORI_TEST_DB_HOST=127.0.0.1 \
TORI_TEST_DB_PORT=3306 \
TORI_TEST_DB_USER=tori_test \
TORI_TEST_DB_PASS=secret \
TORI_TEST_DB_NAME=tori_test \
php8.2 tests/integration/run.php
```

The integration runner recreates its fixture tables before every scenario. It
refuses to use a database without `test` in its name or the database configured
as `DB_NAME` in the project `.env`.
