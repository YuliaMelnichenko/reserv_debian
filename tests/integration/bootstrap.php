<?php

require_once __DIR__ . '/../../inc/database.php';
require_once __DIR__ . '/../test_helpers.php';

function integration_test_config()
{
    $keys = array(
        'host' => 'TORI_TEST_DB_HOST',
        'user' => 'TORI_TEST_DB_USER',
        'password' => 'TORI_TEST_DB_PASS',
        'database' => 'TORI_TEST_DB_NAME',
        'port' => 'TORI_TEST_DB_PORT',
    );
    $config = array();
    $configuredValues = 0;

    foreach ($keys as $name => $environmentKey) {
        $value = getenv($environmentKey);

        if ($value !== false && $value !== '') {
            $configuredValues++;
            $config[$name] = $value;
        }
    }

    if ($configuredValues === 0) {
        return null;
    }

    foreach (array('host', 'user', 'database') as $requiredKey) {
        if (!isset($config[$requiredKey])) {
            throw new RuntimeException('Missing integration test setting: ' . $keys[$requiredKey]);
        }
    }

    $config['password'] = $config['password'] ?? '';
    $config['port'] = isset($config['port']) ? (int)$config['port'] : 3306;

    if (stripos($config['database'], 'test') === false) {
        throw new RuntimeException('Integration database name must contain "test"');
    }

    $productionConfig = @parse_ini_file(__DIR__ . '/../../.env');

    if (
        is_array($productionConfig)
        && isset($productionConfig['DB_NAME'])
        && strcasecmp((string)$productionConfig['DB_NAME'], (string)$config['database']) === 0
    ) {
        throw new RuntimeException('Integration tests refuse to use the configured production database');
    }

    return $config;
}

function integration_test_connect($config)
{
    $link = db_connect(
        $config['host'],
        $config['user'],
        $config['password'],
        $config['database'],
        $config['port']
    );

    if (!$link) {
        throw new RuntimeException('Integration database connection failed: ' . db_connect_error());
    }

    db_set_charset($link, 'utf8mb4');

    return $link;
}

function integration_test_reset_schema($link)
{
    $schema = file_get_contents(__DIR__ . '/schema.sql');

    if ($schema === false || !mysqli_multi_query($link, $schema)) {
        throw new RuntimeException('Unable to prepare integration schema: ' . db_error($link));
    }

    do {
        $result = mysqli_store_result($link);

        if ($result) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($link) && mysqli_next_result($link));

    if (db_error($link) !== '') {
        throw new RuntimeException('Unable to finish integration schema setup: ' . db_error($link));
    }
}

function integration_seed_employee($link, $id, $surname, $supervisorId = null)
{
    $created = db_execute(
        $link,
        "INSERT INTO employees (
            ID, FIRSTNAME, LASTNAME, SURNAME, RELEVANCE, RemoteWork, dayTransitionTime
         ) VALUES (?, 'Тест', 'Тестович', ?, 1, 0, '06:00:00')",
        'is',
        array((int)$id, (string)$surname)
    );

    if (!$created) {
        throw new RuntimeException('Unable to seed integration employee: ' . db_error($link));
    }

    if ($supervisorId !== null) {
        $groupCreated = db_execute(
            $link,
            "INSERT INTO `GROUPS` (USERID, SUPERVISORID, TYPE) VALUES (?, ?, '3')",
            'ii',
            array((int)$id, (int)$supervisorId)
        );

        if (!$groupCreated) {
            throw new RuntimeException('Unable to seed integration supervisor group: ' . db_error($link));
        }
    }
}
