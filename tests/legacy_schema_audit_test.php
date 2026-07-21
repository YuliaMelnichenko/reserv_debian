<?php

return function () {
    $projectRoot = realpath(__DIR__ . '/..');
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS)
    );
    $allowedLegacyAddTimeFiles = array(
        'ajax/get_pause_times_table.php',
        'funcs.php',
        'inc/time_journal_repository.php',
    );
    $legacyAddTimeFiles = array();

    foreach ($iterator as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }

        $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($projectRoot) + 1));
        if (strpos($relativePath, 'tests/') === 0) {
            continue;
        }

        $source = file_get_contents($file->getPathname());
        if (preg_match('/\b(?:STARTDATE|STARTTIME|STOPTIME)\b/', $source)) {
            $legacyAddTimeFiles[] = $relativePath;
        }

        test_assert_same(
            0,
            preg_match('/UPDATE\s+ADD_TIME\s+SET\s+(?:STARTDATE|STARTTIME|STOPTIME)\b/i', $source),
            'Legacy ADD_TIME columns must not be updated in ' . $relativePath
        );
        test_assert_same(
            0,
            preg_match('/INSERT\s+INTO\s+ADD_TIME\s*\([^)]*\b(?:STARTDATE|STARTTIME|STOPTIME)\b/is', $source),
            'Legacy ADD_TIME columns must not be inserted in ' . $relativePath
        );
    }

    sort($legacyAddTimeFiles);
    sort($allowedLegacyAddTimeFiles);
    test_assert_same(
        $allowedLegacyAddTimeFiles,
        $legacyAddTimeFiles,
        'Legacy ADD_TIME reads must stay inside the documented compatibility layer'
    );

    $auditSql = file_get_contents($projectRoot . '/sql/legacy_datetime_audit.sql');
    $sqlWithoutComments = preg_replace('/^\s*--.*$/m', '', $auditSql);
    test_assert_same(
        0,
        preg_match('/\b(?:UPDATE|DELETE|INSERT|ALTER|DROP|TRUNCATE|REPLACE)\b/i', $sqlWithoutComments),
        'Legacy database audit SQL must remain read-only'
    );
};
