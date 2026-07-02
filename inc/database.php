<?php

function db_prepare_and_execute($link, $sql, $types = '', $params = array())
{
    $stmt = mysqli_prepare($link, $sql);

    if (!$stmt) {
        return false;
    }

    if ($types !== '') {
        $bindArgs = array($stmt, $types);

        foreach ($params as $index => $value) {
            $bindArgs[] = &$params[$index];
        }

        if (!call_user_func_array('mysqli_stmt_bind_param', $bindArgs)) {
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    return $stmt;
}

function db_query($link, $sql, $types = '', $params = array())
{
    $stmt = db_prepare_and_execute($link, $sql, $types, $params);

    if (!$stmt) {
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}

function db_execute($link, $sql, $types = '', $params = array())
{
    $stmt = db_prepare_and_execute($link, $sql, $types, $params);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_close($stmt);
    return true;
}
