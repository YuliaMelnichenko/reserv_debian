<?php

require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

date_default_timezone_set('Asia/Novosibirsk');

include_once __DIR__ . '/../funcs.php';
include __DIR__ . '/../php_tori/connect.php';
require_once __DIR__ . '/../inc/time_registration_panel.php';

render_time_registration_panel($link);
