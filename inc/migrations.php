<?php

require_once __DIR__ . '/database.php';

function migration_catalog($directory)
{
    if (!is_dir($directory)) {
        throw new RuntimeException('Migration directory is missing: ' . $directory);
    }

    $files = scandir($directory);

    if ($files === false) {
        throw new RuntimeException('Migration directory cannot be read: ' . $directory);
    }

    $migrations = array();

    foreach ($files as $fileName) {
        if (!str_ends_with($fileName, '.sql')) {
            continue;
        }

        if (!preg_match('/^(\d{3})_([a-z0-9_]+)\.sql$/', $fileName, $matches)) {
            throw new RuntimeException('Migration file must begin with a numeric version: ' . $fileName);
        }

        $path = $directory . DIRECTORY_SEPARATOR . $fileName;
        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException('Migration is empty or unreadable: ' . $fileName);
        }

        $id = $matches[1];

        if (isset($migrations[$id])) {
            throw new RuntimeException('Migration version is duplicated: ' . $id);
        }

        $migrations[$id] = array(
            'id' => $id,
            'file_name' => $fileName,
            'path' => $path,
            'checksum' => hash('sha256', $contents),
        );
    }

    ksort($migrations, SORT_STRING);

    if ($migrations === array()) {
        throw new RuntimeException('No versioned SQL migrations were found');
    }

    return array_values($migrations);
}

function migrations_create_ledger($link)
{
    return db_execute($link, "
        CREATE TABLE IF NOT EXISTS schema_migrations (
          VERSION VARCHAR(32) NOT NULL,
          FILE_NAME VARCHAR(255) NOT NULL,
          CHECKSUM CHAR(64) NOT NULL,
          APPLIED_DT DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (VERSION)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function migrations_read_applied($link)
{
    $result = db_query($link, '
        SELECT VERSION, FILE_NAME, CHECKSUM
        FROM schema_migrations
        ORDER BY VERSION
    ');

    if (!$result) {
        return false;
    }

    $applied = array();

    while ($row = db_fetch_one($result)) {
        $applied[(string)$row['VERSION']] = array(
            'file_name' => (string)$row['FILE_NAME'],
            'checksum' => (string)$row['CHECKSUM'],
        );
    }

    return $applied;
}

function migrations_get_pending($link, $migrations)
{
    $ledgerExists = db_query($link, "SHOW TABLES LIKE 'schema_migrations'");

    if ($ledgerExists === false) {
        return false;
    }

    $applied = db_has_rows($ledgerExists) ? migrations_read_applied($link) : array();

    if ($applied === false) {
        return false;
    }

    $pending = array();

    foreach ($migrations as $migration) {
        $version = $migration['id'];

        if (!isset($applied[$version])) {
            $pending[] = $migration;
            continue;
        }

        if (!hash_equals($applied[$version]['checksum'], $migration['checksum'])) {
            throw new RuntimeException('Applied migration was changed: ' . $migration['file_name']);
        }
    }

    return $pending;
}

function migrations_apply_pending($link, $migrations)
{
    if (!migrations_create_ledger($link)) {
        return false;
    }

    $pending = migrations_get_pending($link, $migrations);

    if ($pending === false) {
        return false;
    }

    $applied = array();

    foreach ($pending as $migration) {
        $contents = file_get_contents($migration['path']);

        if ($contents === false || !db_execute_sql_script($link, $contents)) {
            return false;
        }

        if (!db_execute(
            $link,
            'INSERT INTO schema_migrations (VERSION, FILE_NAME, CHECKSUM) VALUES (?, ?, ?)',
            'sss',
            array($migration['id'], $migration['file_name'], $migration['checksum'])
        )) {
            return false;
        }

        $applied[] = $migration;
    }

    return $applied;
}
