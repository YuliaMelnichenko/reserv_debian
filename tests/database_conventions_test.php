<?php

return function () {
    $ajaxFiles = new DirectoryIterator(__DIR__ . '/../ajax');

    foreach ($ajaxFiles as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }

        $source = file_get_contents($file->getPathname());
        $fileName = $file->getFilename();

        test_assert_same(
            0,
            preg_match('/\bmysqli_(?:begin_transaction|commit|rollback)\s*\(/', $source),
            'AJAX transactions must use DatabaseTransaction in ' . $fileName
        );
        test_assert_same(
            0,
            preg_match('/(?<!ajax_)database_error_message\s*\(/', $source),
            'AJAX database errors must use the shared response helper in ' . $fileName
        );
    }
};
