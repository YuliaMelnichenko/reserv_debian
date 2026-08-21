<?php

require_once __DIR__ . '/database.php';

function deadline_load_employee_summary($link, $userId)
{
    $userId = (int)$userId;

    if ($userId <= 0) {
        return null;
    }

    $employeeResult = db_query(
        $link,
        'SELECT STATE, SURNAME, FIRSTNAME, LASTNAME FROM employees WHERE id = ?',
        'i',
        array($userId)
    );

    if (!$employeeResult) {
        return false;
    }

    $employee = db_fetch_one($employeeResult);

    if (!$employee) {
        return null;
    }

    $departmentResult = db_query(
        $link,
        'SELECT NAME, ROOM
         FROM departments
         WHERE ID IN (SELECT DEPID FROM `GROUPS` WHERE USERID = ?)
         LIMIT 1',
        'i',
        array($userId)
    );

    if (!$departmentResult) {
        return false;
    }

    $department = db_fetch_one($departmentResult);

    return array(
        'employee' => $employee,
        'department' => $department === null
            ? array('NAME' => '', 'ROOM' => '')
            : $department,
    );
}
