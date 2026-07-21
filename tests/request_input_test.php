<?php

require_once __DIR__ . '/../inc/request.php';

return function () {
    $input = array(
        'integer' => '42',
        'negative' => '-1',
        'text' => '  value  ',
        'empty' => '',
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

    $originalPost = $_POST;
    $_POST = $input;
    test_assert_same(42, request_post_int('integer'), 'POST integer helper must normalize values');
    test_assert_same('value', request_post_trimmed_string('text'), 'POST string helper must trim values');
    test_assert_same(false, request_post_has('array'), 'POST presence helper must reject arrays');
    $_POST = $originalPost;

    $migratedEndpoints = array(
        'adj_in_time.php',
        'add_time_part_certain.php',
        'add_time_part_range.php',
        'delete_user_visitiong_info_by_currentDay.php',
        'del_time_part.php',
        'remote_work.php',
        'resume_from_pause.php',
        'set_accounting_error_comment.php',
        'set_accounting_error_status.php',
        'set_add_times_info.php',
        'set_add_times_state.php',
        'set_alert_viewed.php',
        'set_change_out_time.php',
        'set_change_stop_eat.php',
        'set_delay_by_entrance.php',
        'set_delay_explanation.php',
        'set_delay_penalty_info.php',
        'set_delay_state.php',
        'set_pause.php',
        'set_pause_sport.php',
        'set_user_alert.php',
        'switch_day_state.php',
        'time_delete.php',
    );

    foreach ($migratedEndpoints as $endpoint) {
        $source = file_get_contents(__DIR__ . '/../ajax/' . $endpoint);
        test_assert_same(
            false,
            strpos($source, '$_POST['),
            'Migrated endpoint must use request helpers: ' . $endpoint
        );
    }
};
