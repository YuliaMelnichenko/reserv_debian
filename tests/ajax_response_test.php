<?php

require_once __DIR__ . '/../inc/ajax_response.php';

return function () {
    test_assert_same(
        '{"status":"success","message":"Готово"}',
        ajax_encode_json(array('status' => 'success', 'message' => 'Готово')),
        'AJAX JSON must preserve Unicode and the existing status contract'
    );

    $invalidUtf8 = "invalid\xB1";
    $encoded = ajax_encode_json(array('status' => 'error', 'message' => $invalidUtf8));
    test_assert_same(true, strpos($encoded, '"status":"error"') !== false, 'Invalid UTF-8 must not break the JSON response');

    ob_start();
    ajax_text_response('1');
    $textResponse = ob_get_clean();
    test_assert_same('1', $textResponse, 'Plain-text AJAX responses must preserve legacy numeric contracts');

    ob_start();
    ajax_json_response(array('valid' => 1));
    $jsonResponse = ob_get_clean();
    test_assert_same('{"valid":1}', $jsonResponse, 'JSON AJAX responses must preserve payload fields');

    $authEndpoint = file_get_contents(__DIR__ . '/../ajax/auth.php');
    $authPage = file_get_contents(__DIR__ . '/../auth.php');
    test_assert_true(
        strpos($authEndpoint, '$_SESSION = array();') !== false
            && strpos($authEndpoint, 'session_regenerate_id(true);') !== false,
        'Signing in as another employee must reset the prior employee session state'
    );
    test_assert_true(
        strpos($authPage, "window.location = 'index.php';") !== false,
        'A successful sign-in must open the current-day page directly'
    );
};
