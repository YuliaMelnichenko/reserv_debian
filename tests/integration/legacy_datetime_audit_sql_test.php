<?php

return function ($link) {
    $auditSql = file_get_contents(__DIR__ . '/../../sql/legacy_datetime_audit.sql');

    test_assert_true($auditSql !== false, 'Legacy datetime audit SQL must be readable');
    test_assert_same(
        true,
        db_execute_sql_script($link, $auditSql),
        'Legacy datetime audit must run on a schema where historical columns are absent: ' . db_error($link)
    );
};
