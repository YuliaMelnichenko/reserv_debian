<?php

return function () {
    $ajaxDirectory = realpath(__DIR__ . '/../ajax');
    $files = new DirectoryIterator($ajaxDirectory);

    foreach ($files as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }

        $source = file_get_contents($file->getPathname());
        $fileName = $file->getFilename();

        test_assert_same(
            0,
            preg_match('/header\s*\(\s*[\'\"](?:Content-Type|Cache-Control|Pragma|Expires)\s*:/i', $source),
            'AJAX response headers must use the shared helper in ' . $fileName
        );

        if (!preg_match('/\bajax_(?:text|json|response)_headers\s*\(/', $source)) {
            continue;
        }

        $loadsResponseHelper = strpos($source, 'inc/access.php') !== false
            || strpos($source, 'inc/ajax_response.php') !== false;

        test_assert_true(
            $loadsResponseHelper,
            'AJAX header helper must be loaded explicitly in ' . $fileName
        );
    }
};
