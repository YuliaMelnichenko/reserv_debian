<?php

return function () {
    $root = realpath(__DIR__ . '/..');
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    $deprecatedPatterns = array(
        '/\((?:boolean|integer|double|binary)\)/i' => 'non-canonical cast',
        '/\bFILTER_SANITIZE_STRING\b/' => 'FILTER_SANITIZE_STRING',
        '/\b(?:__sleep|__wakeup)\s*\(/i' => 'legacy serialization magic method',
        '/\b(?:get_class|get_parent_class)\s*\(\s*\)/i' => 'argument-less class inspection',
        '/\barray_key_exists\s*\(\s*null\s*,/i' => 'null array key',
        '/^\s*case\s+[^:\r\n]+;/mi' => 'semicolon after case',
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }

        $path = $file->getPathname();
        $relativePath = str_replace('\\', '/', substr($path, strlen($root) + 1));

        if (strpos($relativePath, 'tests/') === 0 || strpos($relativePath, '.git/') === 0) {
            continue;
        }

        $source = file_get_contents($path);
        $codeOnly = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (in_array($token[0], array(
                    T_CONSTANT_ENCAPSED_STRING,
                    T_ENCAPSED_AND_WHITESPACE,
                    T_COMMENT,
                    T_DOC_COMMENT,
                    T_INLINE_HTML,
                ), true)) {
                    $codeOnly .= str_repeat(' ', strlen($token[1]));
                } else {
                    $codeOnly .= $token[1];
                }
                continue;
            }

            test_assert_true($token !== '`', 'PHP 8.5 deprecated backtick operator found in ' . $relativePath);
            $codeOnly .= $token;
        }

        foreach ($deprecatedPatterns as $pattern => $description) {
            test_assert_same(
                0,
                preg_match($pattern, $codeOnly),
                'PHP 8.5 deprecated ' . $description . ' found in ' . $relativePath
            );
        }

        if ($relativePath !== 'inc/request.php') {
            test_assert_same(
                0,
                preg_match('/\$_(?:GET|POST|REQUEST)\s*\[/', $codeOnly),
                'Request value bypasses strict input helpers in ' . $relativePath
            );
        }
    }
};
