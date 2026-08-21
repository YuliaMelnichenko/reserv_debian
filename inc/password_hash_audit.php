<?php

require_once __DIR__ . '/database.php';

function password_hash_audit_has_modern_column($link)
{
    $result = db_query($link, "SHOW COLUMNS FROM employees LIKE 'PASSWORD_HASH'");

    if ($result === false) {
        return false;
    }

    return db_has_rows($result);
}

function password_hash_audit_empty_summary()
{
    return array(
        'total' => 0,
        'modern_hash' => 0,
        'legacy_md5' => 0,
        'without_supported_hash' => 0,
    );
}

function password_hash_audit_get_summary($link)
{
    $hasModernColumn = password_hash_audit_has_modern_column($link);

    if ($hasModernColumn === false) {
        return false;
    }

    $summary = array(
        'migration_applied' => $hasModernColumn,
        'active' => password_hash_audit_empty_summary(),
        'archived' => password_hash_audit_empty_summary(),
    );

    if (!$hasModernColumn) {
        return $summary;
    }

    $result = db_query($link, "
        SELECT
          CASE WHEN RELEVANCE = 1 THEN 'active' ELSE 'archived' END AS account_state,
          COUNT(*) AS total,
          SUM(CASE WHEN TRIM(COALESCE(PASSWORD_HASH, '')) <> '' THEN 1 ELSE 0 END) AS modern_hash,
          SUM(CASE
            WHEN TRIM(COALESCE(PASSWORD_HASH, '')) = ''
             AND passwd REGEXP '^[0-9A-Fa-f]{32}$'
            THEN 1
            ELSE 0
          END) AS legacy_md5,
          SUM(CASE
            WHEN TRIM(COALESCE(PASSWORD_HASH, '')) = ''
             AND passwd NOT REGEXP '^[0-9A-Fa-f]{32}$'
            THEN 1
            ELSE 0
          END) AS without_supported_hash
        FROM employees
        GROUP BY CASE WHEN RELEVANCE = 1 THEN 'active' ELSE 'archived' END
    ");

    if ($result === false) {
        return false;
    }

    while ($row = db_fetch_one($result)) {
        $state = (string)$row['account_state'];

        if (!isset($summary[$state])) {
            continue;
        }

        $summary[$state] = array(
            'total' => (int)$row['total'],
            'modern_hash' => (int)$row['modern_hash'],
            'legacy_md5' => (int)$row['legacy_md5'],
            'without_supported_hash' => (int)$row['without_supported_hash'],
        );
    }

    return $summary;
}
