<?php

require_once __DIR__ . '/../inc/request.php';

return function () {
    $input = array(
        'integer' => '42',
        'negative' => '-1',
        'text' => '  value  ',
        'empty' => '',
        'date' => '2026-07-21',
        'time' => '08:30',
        'datetime' => '2026-07-21T08:30',
        'array' => array('unexpected'),
        'null' => null,
    );

    test_assert_same(42, request_int_value($input, 'integer'), 'Integer input must be normalized');
    test_assert_same(-1, request_int_value($input, 'negative'), 'Negative integer input must be preserved');
    test_assert_same('  value  ', request_string_value($input, 'text'), 'String input must preserve whitespace');
    test_assert_same('value', request_trimmed_string_value($input, 'text'), 'Trimmed string input must remove outer whitespace');
    test_assert_same('', request_string_value($input, 'empty', 'fallback'), 'An empty string must not become a fallback');
    test_assert_same(7, request_int_value($input, 'array', 7), 'Array input must use the safe integer fallback');
    test_assert_same('fallback', request_string_value($input, 'null', 'fallback'), 'Null input must use the fallback');
    test_assert_same(false, request_has_scalar_value($input, 'array'), 'Array input must not count as a scalar value');
    test_assert_same(false, request_has_scalar_value($input, 'missing'), 'Missing input must not count as present');
    test_assert_same('2026-07-21', request_date_value($input, 'date'), 'Date input must be normalized');
    test_assert_same('08:30:00', request_time_value($input, 'time'), 'Time input must be normalized');
    test_assert_same('2026-07-21 08:30:00', request_datetime_value($input, 'datetime'), 'Datetime input must be normalized');

    $originalPost = $_POST;
    $_POST = $input;
    test_assert_same(42, request_post_int('integer'), 'POST integer helper must normalize values');
    test_assert_same('value', request_post_trimmed_string('text'), 'POST string helper must trim values');
    test_assert_same(false, request_post_has('array'), 'POST presence helper must reject arrays');
    $_POST = $originalPost;

    $ajaxFiles = new DirectoryIterator(__DIR__ . '/../ajax');

    foreach ($ajaxFiles as $endpoint) {
        if (!$endpoint->isFile() || strtolower($endpoint->getExtension()) !== 'php') {
            continue;
        }

        $source = file_get_contents($endpoint->getPathname());
        test_assert_same(
            false,
            strpos($source, '$_POST['),
            'AJAX endpoint must use request helpers: ' . $endpoint->getFilename()
        );
    }
};
