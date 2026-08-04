<?php

require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

if (!request_post_has('next')) {
    echo 'Ошибка: не передано направление изменения состояния';
    exit;
}

include_once __DIR__ . '/../php_tori/connect.php';
include_once __DIR__ . '/../funcs.php';
require_once __DIR__ . '/../inc/workday_transition_service.php';

echo process_workday_transition($link, $_SESSION, request_post_int('next'));
