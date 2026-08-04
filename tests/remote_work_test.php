<?php

require_once __DIR__ . '/../inc/remote_work.php';

return function () {
    test_assert_same(
        array('status' => 'success'),
        remote_work_result('success'),
        'A successful remote work operation must preserve the JSON contract'
    );
    test_assert_same(
        array('status' => 'error', 'message' => 'Ошибка'),
        remote_work_result('error', 'Ошибка'),
        'A failed remote work operation must preserve its message'
    );

    $controller = file_get_contents(__DIR__ . '/../ajax/remote_work.php');

    test_assert_same(
        0,
        preg_match('/\bmysqli_(?:prepare|stmt_)\w*\s*\(/', $controller),
        'The remote work controller must use the shared service instead of direct statements'
    );
    test_assert_true(
        strpos($controller, "inc/remote_work.php") !== false,
        'The remote work controller must load the shared service'
    );
};
