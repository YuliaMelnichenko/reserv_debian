<?php

require_once __DIR__ . '/database.php';

function get_sv_name_by_userid($user_id)
{
    include __DIR__ . '/../php_tori/connect.php';

    $query0 = db_query(
        $link,
        'SELECT SUPERVISORID FROM GROUPS WHERE TYPE = 100 AND USERID = ?',
        'i',
        array((int)$user_id)
    );

    if (!$query0) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return '';
    }

    $row0 = mysqli_fetch_assoc($query0);

    if (!$row0) {
        return '';
    }

    $query = db_query(
        $link,
        'SELECT FIRSTNAME, LASTNAME, SURNAME FROM employees WHERE ID = ?',
        'i',
        array((int)$row0['SUPERVISORID'])
    );

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return '';
    }

    $row = mysqli_fetch_assoc($query);

    if (!$row) {
        return 'Unknown. Error 2';
    }

    return $row['SURNAME'] . ' ' . $row['FIRSTNAME'] . ' ' . $row['LASTNAME'];
}

function get_group_user_info_by_svID_for_report_ex($svID)
{
    include __DIR__ . '/../php_tori/connect.php';

    $userIDs = array();
    mysqli_set_charset($link, 'utf8');
    $dirID = isset($_SESSION['ss_id']) ? (int)$_SESSION['ss_id'] : 0;

    if ($dirID !== 0) {
        if ($dirID !== 1) {
            if ($svID != -1) {
                $query0 = db_query(
                    $link,
                    'SELECT USERID FROM GROUPS WHERE SUPERVISORID = ? AND (TYPE = 0 OR TYPE = -1) GROUP BY USERID',
                    'i',
                    array((int)$svID)
                );
            }
            else {
                $query0 = db_query($link, 'SELECT USERID FROM GROUPS WHERE TYPE = 0 OR TYPE = -1 GROUP BY USERID');
            }
        }
        else {
            if ($svID != -1) {
                $query0 = db_query(
                    $link,
                    'SELECT e.id FROM GROUPS g INNER JOIN employees e ON g.USERID = e.id '
                        . 'WHERE g.SUPERVISORID = ? AND (g.TYPE = 0 OR g.TYPE = -1) ORDER BY e.surname',
                    'i',
                    array((int)$svID)
                );
            }
            else {
                $query0 = db_query(
                    $link,
                    'SELECT e.id FROM GROUPS g INNER JOIN employees e ON g.USERID = e.id '
                        . 'WHERE g.TYPE = 0 OR g.TYPE = -1 ORDER BY e.surname'
                );
            }
        }

        if (!$query0) {
            echo database_error_message($link, __FILE__ . ':' . __LINE__);
        }
        else if (mysqli_num_rows($query0) === 0) {
            $userIDs[] = $svID;
        }
        else {
            while ($row = mysqli_fetch_assoc($query0)) {
                $userIDs[] = isset($row['USERID']) ? $row['USERID'] : $row['id'];
            }
        }
    }

    $newUserIDs = array();
    $ownUserID = isset($_SESSION['ss_id']) ? (int)$_SESSION['ss_id'] : -1;

    if ($ownUserID !== -1 && $ownUserID !== 500 && $ownUserID !== 501) {
        $newUserIDs[] = $ownUserID;
    }

    foreach ($userIDs as $userID) {
        if ((int)$userID !== $ownUserID) {
            $newUserIDs[] = $userID;
        }
    }

    $usersRate = array();
    $usersFIO = array();
    $usersNameParts = array();

    foreach ($newUserIDs as $userID) {
        $query = db_query(
            $link,
            'SELECT rate, firstname, lastname, surname FROM employees WHERE ID = ?',
            'i',
            array((int)$userID)
        );

        if (!$query) {
            echo database_error_message($link, __FILE__ . ':' . __LINE__);
            continue;
        }

        $row = mysqli_fetch_assoc($query);

        if (!$row) {
            continue;
        }

        $surname = isset($row['surname']) ? $row['surname'] : '';
        $firstname = isset($row['firstname']) ? $row['firstname'] : '';
        $lastname = isset($row['lastname']) ? $row['lastname'] : '';
        $usersRate[] = $row['rate'];
        $usersFIO[] = trim($surname . ' ' . $firstname . ' ' . $lastname);
        $usersNameParts[] = array(
            'surname' => $surname,
            'firstname' => $firstname,
            'lastname' => $lastname,
        );
    }

    return array(
        0 => $newUserIDs,
        1 => $usersFIO,
        2 => $usersRate,
        8 => $usersNameParts,
    );
}

function am_i_superuser($userID)
{
    include __DIR__ . '/../php_tori/connect.php';

    $query = db_query(
        $link,
        'SELECT 1 FROM GROUPS WHERE SUPERVISORID = ? AND TYPE <> -1 LIMIT 1',
        'i',
        array((int)$userID)
    );

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return 0;
    }

    return mysqli_num_rows($query) > 0 ? 1 : 0;
}

function get_user_rate($userID)
{
    include __DIR__ . '/../php_tori/connect.php';

    $query = db_query($link, 'SELECT RATE FROM employees WHERE ID = ?', 'i', array((int)$userID));

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return 40;
    }

    $row = mysqli_fetch_assoc($query);
    return $row ? $row['RATE'] : 40;
}

function get_superuser_names_by_user_id($userID)
{
    include __DIR__ . '/../php_tori/connect.php';
    mysqli_set_charset($link, 'utf8');

    $query = db_query($link, "
        SELECT DISTINCT ID, FIRSTNAME, LASTNAME, SURNAME
        FROM employees
        WHERE ID IN (SELECT SUPERVISORID FROM GROUPS WHERE USERID = ?)
    ", 'i', array((int)$userID));

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return array();
    }

    $result = array();

    while ($row = mysqli_fetch_assoc($query)) {
        $result[] = array(
            $row['SURNAME'] . ' ' . $row['FIRSTNAME'] . ' ' . $row['LASTNAME'],
            $row['ID'],
        );
    }

    return $result;
}

function get_superuser_name_by_id($userID)
{
    return get_user_name_by_id($userID);
}

function get_user_name_by_id($userID)
{
    include __DIR__ . '/../php_tori/connect.php';
    mysqli_set_charset($link, 'utf8');

    $query = db_query(
        $link,
        'SELECT FIRSTNAME, LASTNAME, SURNAME FROM employees WHERE ID = ?',
        'i',
        array((int)$userID)
    );

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return '';
    }

    $row = mysqli_fetch_assoc($query);

    if (!$row) {
        return '';
    }

    return $row['SURNAME'] . ' ' . $row['FIRSTNAME'] . ' ' . $row['LASTNAME'];
}

function get_pause_agree_able_superusers_by_userID($userID)
{
    include __DIR__ . '/../php_tori/connect.php';
    mysqli_set_charset($link, 'utf8');

    $query = db_query(
        $link,
        'SELECT SUPERVISORID FROM GROUPS WHERE USERID = ? AND TYPE = 3',
        'i',
        array((int)$userID)
    );

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return array();
    }

    $result = array();

    while ($row = mysqli_fetch_assoc($query)) {
        $supervisorID = $row['SUPERVISORID'];
        $result[] = array($supervisorID, get_superuser_name_by_id($supervisorID));
    }

    return $result;
}

function get_users_by_superusers_and_type($supervisorID, $type)
{
    include __DIR__ . '/../php_tori/connect.php';
    mysqli_set_charset($link, 'utf8');

    $query = db_query($link, "
        SELECT g.USERID
        FROM GROUPS g
        INNER JOIN employees e ON g.USERID = e.ID
        WHERE g.SUPERVISORID = ?
          AND g.TYPE = ?
        ORDER BY e.SURNAME ASC
    ", 'ii', array((int)$supervisorID, (int)$type));

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return array();
    }

    $result = array();

    while ($row = mysqli_fetch_assoc($query)) {
        $result[] = $row['USERID'];
    }

    return $result;
}

function get_user_defStartTime_and_allowedDelay($userID, &$user_defaultStartTime, &$user_allowedDelay)
{
    include __DIR__ . '/../php_tori/connect.php';

    $query = db_query(
        $link,
        'SELECT defaultStartTime, AllowedDelayMinutes FROM employees WHERE ID = ?',
        'i',
        array((int)$userID)
    );

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return 0;
    }

    $row = mysqli_fetch_assoc($query);

    if (!$row) {
        return 0;
    }

    $user_defaultStartTime = $row['defaultStartTime'];
    $user_allowedDelay = $row['AllowedDelayMinutes'];
    return 1;
}

function get_and_update_start_time_status($userID)
{
    include __DIR__ . '/../php_tori/connect.php';

    $currentTime = get_splited_current_date_time_in_timezone()[1];
    $isThereDelay = 0;
    $defaultStartTime = '';
    $allowedDelay = 0;
    $defaultStartTimeWithDelay = '';
    $defaultStartTimeWithDelayValue = 0;
    $remoteWork = 0;
    $query = db_query(
        $link,
        'SELECT defaultStartTime, allowedDelayMinutes, remoteWork FROM employees WHERE ID = ?',
        'i',
        array((int)$userID)
    );

    if (!$query) {
        echo database_error_message($link, __FILE__ . ':' . __LINE__);
        return array(
            $isThereDelay,
            $defaultStartTime,
            $allowedDelay,
            $defaultStartTimeWithDelay,
            $defaultStartTimeWithDelayValue,
            $remoteWork,
        );
    }

    $row = mysqli_fetch_assoc($query);

    if ($row) {
        $defaultStartTime = $row['defaultStartTime'];
        $allowedDelay = $row['allowedDelayMinutes'];
        $remoteWork = $row['remoteWork'];
        $_SESSION['ss_RemoteWorkStr'] = 'В ОФИСЕ';
        $_SESSION['ss_RemoteWork'] = 0;

        if ((int)$remoteWork === 1) {
            $_SESSION['ss_RemoteWork'] = 1;
            $_SESSION['ss_RemoteWorkStr'] = 'УДАЛЕННЫЙ';
        }

        $_SESSION['ss_defaultStartTime'] = $defaultStartTime;
        $_SESSION['ss_allowedDelay'] = $allowedDelay;
        $defaultStartTimeWithDelay = date(
            'H:i:s',
            strtotime($defaultStartTime . ' + ' . $allowedDelay . ' minute')
        );
        $_SESSION['ss_defaultStartTimeWithDelay'] = $defaultStartTimeWithDelay;
        $defaultStartTimeWithDelayValue = strtotime($defaultStartTimeWithDelay);
        $_SESSION['ss_defaultStartTimeWithDelayVal'] = $defaultStartTimeWithDelayValue;

        if ($currentTime > $defaultStartTimeWithDelayValue && (int)$remoteWork !== 1) {
            $isThereDelay = 2;
        }

        $_SESSION['ss_there_is_delay'] = $isThereDelay;
    }

    return array(
        $isThereDelay,
        $defaultStartTime,
        $allowedDelay,
        $defaultStartTimeWithDelay,
        $defaultStartTimeWithDelayValue,
        $remoteWork,
    );
}
