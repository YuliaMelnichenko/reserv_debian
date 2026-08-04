<?php

class DatabaseTransaction
{
    private $commitCallback;
    private $rollbackCallback;
    private $active = true;

    public function __construct($commitCallback, $rollbackCallback)
    {
        $this->commitCallback = $commitCallback;
        $this->rollbackCallback = $rollbackCallback;
    }

    public function commit()
    {
        if (!$this->active) {
            return false;
        }

        $committed = (bool)call_user_func($this->commitCallback);

        if ($committed) {
            $this->active = false;
            return true;
        }

        $this->rollback();
        return false;
    }

    public function rollback()
    {
        if (!$this->active) {
            return true;
        }

        $this->active = false;
        return (bool)call_user_func($this->rollbackCallback);
    }

    public function isActive()
    {
        return $this->active;
    }

    public function __destruct()
    {
        if ($this->active) {
            $this->rollback();
        }
    }
}

function db_transaction_start($link)
{
    if (!mysqli_begin_transaction($link)) {
        return false;
    }

    return new DatabaseTransaction(
        function () use ($link) {
            return mysqli_commit($link);
        },
        function () use ($link) {
            return mysqli_rollback($link);
        }
    );
}

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

function db_execute_affected_rows($link, $sql, $types = '', $params = array())
{
    $stmt = db_prepare_and_execute($link, $sql, $types, $params);

    if (!$stmt) {
        return false;
    }

    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    return $affectedRows;
}

function db_set_charset($link, $charset = 'utf8')
{
    return mysqli_set_charset($link, $charset);
}

function db_error($link)
{
    return mysqli_error($link);
}

function db_fetch_one($result)
{
    if (!$result) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);

    return $row ?: null;
}

function db_fetch_all($result)
{
    $rows = array();

    if (!$result) {
        return $rows;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function db_num_rows($result)
{
    return $result ? mysqli_num_rows($result) : 0;
}

function db_has_rows($result)
{
    return db_num_rows($result) > 0;
}

function db_connect($host, $user, $password, $database, $port = 3306)
{
    return mysqli_connect($host, $user, $password, $database, (int)$port);
}

function db_connect_error()
{
    return mysqli_connect_error();
}

function db_close($link)
{
    return mysqli_close($link);
}

function db_affected_rows($link)
{
    return mysqli_affected_rows($link);
}
