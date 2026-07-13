<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if (!isset($_SESSION['ss_id'])) {
  echo "Ошибка 485";
  exit;
}

if (!isset($_POST['next'])) {
  echo "Ошибка: не передано направление изменения состояния";
  exit;
}

include_once __DIR__ . "/../php_tori/connect.php";
include_once __DIR__ . "/../funcs.php";
require_once __DIR__ . "/../inc/workday_state.php";

mysqli_set_charset($link, "utf8");

$nextState = (int)$_POST['next'];

$dtResult = get_current_datetime_in_timezone();
$dateTimeStr = $dtResult[1];

$id = (int)$_SESSION['ss_id'];

$userDayTransitionTime = isset($_SESSION['ss_dayTransitionTime'])
  ? $_SESSION['ss_dayTransitionTime']
  : "06:00:00";

$dateArr = datetimestr_to_day_start_stop_DT_ex_str_idx($dateTimeStr, $userDayTransitionTime);

$startDTStr = $dateArr[0];
$stopDTStr = $dateArr[1];

$maxOpenShiftHours = 3;
$maxOpenShiftSeconds = $maxOpenShiftHours * 60 * 60;

function reset_time_registration_session()
{
  $_SESSION['ss_state'] = 1;
  $_SESSION['ss_visiting_ID'] = 0;
}

function sync_time_registration_state_from_db($link, $userID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds)
{
  $query = db_query($link, "
    SELECT ID, state
    FROM visiting
    WHERE user_id = ?
      AND (
        (
          in_dt >= ?
          AND in_dt < ?
        )
        OR
        (
          state != 0
          AND in_dt < ?
          AND TIMESTAMPDIFF(SECOND, ?, ?) <= ?
        )
      )
    ORDER BY in_dt DESC, ID DESC
    LIMIT 1
  ", 'isssssi', array((int)$userID, $startDTStr, $stopDTStr, $startDTStr, $startDTStr, $dateTimeStr, (int)$maxOpenShiftSeconds));

  if (!$query) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  if (mysqli_num_rows($query) == 0) {
    $_SESSION['ss_state'] = 1;
    $_SESSION['ss_visiting_ID'] = 0;

    return array(
      "state" => 1,
      "visiting_ID" => 0
    );
  }

  $row = mysqli_fetch_array($query, MYSQLI_ASSOC);

  $_SESSION['ss_state'] = (int)$row["state"];
  $_SESSION['ss_visiting_ID'] = (int)$row["ID"];

  return array(
    "state" => (int)$row["state"],
    "visiting_ID" => (int)$row["ID"]
  );
}

function get_current_visit_row($link, $userID, $visitID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds)
{
  if ($visitID <= 0) {
    return null;
  }

  $visitID = (int)$visitID;

  $query = db_query($link, "
    SELECT ID, user_id, in_dt, eat_start_dt, eat_stop_dt, out_dt, state
    FROM visiting
    WHERE ID = ?
      AND user_id = ?
    LIMIT 1
  ", 'ii', array($visitID, (int)$userID));

  if (!$query) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
    exit;
  }

  if (mysqli_num_rows($query) == 0) {
    return null;
  }

  $visitRow = mysqli_fetch_array($query, MYSQLI_ASSOC);

  $inTime = strtotime($visitRow["in_dt"]);
  $nowTime = strtotime($dateTimeStr);

  if ($inTime === false || $nowTime === false) {
    return null;
  }

  $duration = $nowTime - $inTime;

  if ($duration < 0) {
    return null;
  }

  $isInCurrentPeriod = (
    $visitRow["in_dt"] >= $startDTStr &&
    $visitRow["in_dt"] < $stopDTStr
  );

  $isAllowedOvernightOpenShift = (
    (int)$visitRow["state"] != 0 &&
    $duration <= $maxOpenShiftSeconds
  );

  if (!$isInCurrentPeriod && !$isAllowedOvernightOpenShift) {
    return null;
  }

  return $visitRow;
}

function require_current_visit_row($link, $userID, $visitID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, $expectedState)
{
  $visitRow = get_current_visit_row(
    $link,
    $userID,
    $visitID,
    $startDTStr,
    $stopDTStr,
    $dateTimeStr,
    $maxOpenShiftSeconds
  );

  if ($visitRow === null) {
    reset_time_registration_session();
    echo "Ошибка: текущая запись рабочего дня устарела или не найдена. Обновите страницу и начните регистрацию заново.";
    exit;
  }

  if ((int)$visitRow["state"] != (int)$expectedState) {
    $_SESSION['ss_state'] = (int)$visitRow["state"];
    $_SESSION['ss_visiting_ID'] = (int)$visitRow["ID"];

    echo "Ошибка: состояние рабочего дня уже изменилось. Обновите страницу.";
    exit;
  }

  return $visitRow;
}

$syncedState = sync_time_registration_state_from_db(
  $link,
  $id,
  $startDTStr,
  $stopDTStr,
  $dateTimeStr,
  $maxOpenShiftSeconds
);

$ss_state = (int)$syncedState["state"];
$ss_visiting_ID = (int)$syncedState["visiting_ID"];
$transition = get_workday_transition($ss_state, $nextState);

if ($transition === null) {
  echo "Ошибка: неизвестное состояние регистрации времени.";
  exit;
}

$transitionAction = $transition["action"];
$transitionState = (int)$transition["to"];

error_log(
  "TORI_SWITCH_SYNC user=$id next=$nextState state=$ss_state visit=$ss_visiting_ID start=$startDTStr stop=$stopDTStr now=$dateTimeStr"
);

if ($nextState == 1) {
  if ($transitionAction === WORKDAY_ACTION_ARRIVE) {
    $_SESSION['ss_visiting_ID'] = 0;
    $ss_visiting_ID = 0;

    if (!mysqli_begin_transaction($link)) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    $idQuery = db_query($link, 'SELECT ID FROM visiting ORDER BY ID DESC LIMIT 1 FOR UPDATE');

    if (!$idQuery) {
      $errorMessage = database_error_message($link, __FILE__ . ':' . __LINE__);
      mysqli_rollback($link);
      echo $errorMessage;
      exit;
    }

    $lastVisit = mysqli_fetch_assoc($idQuery);
    $newID = $lastVisit ? (int)$lastVisit['ID'] + 1 : 1;

    $openCheck = db_query($link, "
      SELECT ID, in_dt, state
      FROM visiting
      WHERE user_id = ?
        AND state != 0
        AND (
          (
            in_dt >= ?
            AND in_dt < ?
          )
          OR
          (
            in_dt < ?
            AND TIMESTAMPDIFF(SECOND, ?, ?) <= ?
          )
        )
      ORDER BY in_dt DESC, ID DESC
      LIMIT 1
      FOR UPDATE
    ", 'isssssi', array($id, $startDTStr, $stopDTStr, $startDTStr, $startDTStr, $dateTimeStr, $maxOpenShiftSeconds));

    if (!$openCheck) {
      $errorMessage = database_error_message($link, __FILE__ . ':' . __LINE__);
      mysqli_rollback($link);
      echo $errorMessage;
      exit;
    }

    if (mysqli_num_rows($openCheck) > 0) {
      $openRow = mysqli_fetch_array($openCheck, MYSQLI_ASSOC);

      if (!mysqli_commit($link)) {
        $errorMessage = database_error_message($link, __FILE__ . ':' . __LINE__);
        mysqli_rollback($link);
        echo $errorMessage;
        exit;
      }

      $_SESSION['ss_state'] = (int)$openRow["state"];
      $_SESSION['ss_visiting_ID'] = (int)$openRow["ID"];

      error_log(
        "TORI_SWITCH_BLOCK_INSERT_RECENT user=$id open_visit=" . $openRow["ID"] . " open_state=" . $openRow["state"] . " open_in=" . $openRow["in_dt"]
      );

      echo "Ошибка: у сотрудника уже есть открытый рабочий день от " . $openRow["in_dt"] . ". Новый приход не создан. Обновите страницу.";
      exit;
    }

    error_log(
  "TORI_SWITCH_INSERT user=$id next=$nextState state=$ss_state visit=$ss_visiting_ID now=$dateTimeStr"
);

    $res = db_execute($link, "
      INSERT INTO visiting (
        ID,
        user_id,
        in_dt,
        eat_start_dt,
        eat_stop_dt,
        out_dt,
        state,
        remoteWorkState,
        dayTransitionTime
      )
      SELECT DISTINCT
        ?,
        ?,
        ?,
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        '2',
        b.RemoteWork,
        b.dayTransitionTime
      FROM employees b
      WHERE b.ID = ?
    ", 'iisi', array($newID, $id, $dateTimeStr, $id));

    if (!$res) {
      $errorMessage = database_error_message($link, __FILE__ . ':' . __LINE__);
      mysqli_rollback($link);
      echo $errorMessage;
      exit;
    }

    if (!mysqli_commit($link)) {
      $errorMessage = database_error_message($link, __FILE__ . ':' . __LINE__);
      mysqli_rollback($link);
      echo $errorMessage;
      exit;
    }

    $_SESSION['ss_state'] = $transitionState;
    $_SESSION['ss_visiting_ID'] = $newID;

    echo "1";
    exit;
  }

  error_log(
  "TORI_SWITCH_LUNCH_START user=$id next=$nextState state=$ss_state visit=$ss_visiting_ID now=$dateTimeStr"
);

  if ($transitionAction === WORKDAY_ACTION_START_LUNCH) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 2);

    if (strtotime($dateTimeStr) <= strtotime($visitRow["in_dt"])) {
      echo "Ошибка: время начала обеда не может быть меньше или равно времени прихода.";
      exit;
    }

    $affectedRows = db_execute_affected_rows($link, "
      UPDATE visiting
      SET eat_start_dt = ?,
          state = 3
      WHERE user_id = ?
        AND ID = ?
    ", 'sii', array($dateTimeStr, $id, $ss_visiting_ID));

    if ($affectedRows === false) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if ($affectedRows <= 0) {
      echo "Ошибка: не удалось начать обед. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = $transitionState;

    echo "1";
    exit;
  }

  if ($transitionAction === WORKDAY_ACTION_FINISH_LUNCH) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 3);

    if ($visitRow["eat_start_dt"] == "0000-00-00 00:00:00") {
      echo "Ошибка: нельзя завершить обед, потому что время начала обеда не найдено.";
      exit;
    }

    if (strtotime($dateTimeStr) <= strtotime($visitRow["eat_start_dt"])) {
      echo "Ошибка: время окончания обеда не может быть меньше или равно времени начала обеда.";
      exit;
    }

    $affectedRows = db_execute_affected_rows($link, "
      UPDATE visiting
      SET eat_stop_dt = ?,
          state = 4
      WHERE user_id = ?
        AND ID = ?
    ", 'sii', array($dateTimeStr, $id, $ss_visiting_ID));

    if ($affectedRows === false) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if ($affectedRows <= 0) {
      echo "Ошибка: не удалось завершить обед. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = $transitionState;

    echo "1";
    exit;
  }

  if ($transitionAction === WORKDAY_ACTION_LEAVE) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 4);

    if ($visitRow === null) {
      echo "Ошибка: не найдена активная запись рабочего дня. Обновите страницу.";
      exit;
    }

    $visitID = (int)$visitRow["ID"];
    $dbState = (int)$visitRow["state"];

    if ($dbState == 0) {
      $_SESSION['ss_state'] = 0;
      $_SESSION['ss_visiting_ID'] = $visitID;

      echo "1";
      exit;
    }

    if (strtotime($dateTimeStr) <= strtotime($visitRow["in_dt"])) {
      echo "Ошибка: время ухода не может быть меньше или равно времени прихода.";
      exit;
    }

    $visitingAffectedRows = db_execute_affected_rows($link, "
      UPDATE visiting
      SET out_dt = ?,
          state = 0
      WHERE user_id = ?
        AND ID = ?
        AND state = 4
    ", 'sii', array($dateTimeStr, $id, $visitID));

    if ($visitingAffectedRows === false) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    $res2 = db_execute($link, "
      UPDATE remote_work
      SET stop_dt = NOW()
      WHERE user_id = ?
        AND stop_dt IS NULL
    ", 'i', array($id));

    if (!$res2) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if ($visitingAffectedRows <= 0) {
      $checkQuery = db_query($link, "
        SELECT ID, state, out_dt
        FROM visiting
        WHERE user_id = ?
          AND ID = ?
        LIMIT 1
      ", 'ii', array($id, $visitID));

      if ($checkQuery && mysqli_num_rows($checkQuery) > 0) {
        $checkRow = mysqli_fetch_array($checkQuery, MYSQLI_ASSOC);

        if ((int)$checkRow["state"] == 0) {
          $_SESSION['ss_state'] = 0;
          $_SESSION['ss_visiting_ID'] = $visitID;

          echo "1";
          exit;
        }
      }

      echo "Ошибка: не удалось зарегистрировать уход. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = $transitionState;
    $_SESSION['ss_visiting_ID'] = $visitID;

    echo "1";
    exit;
  }

  echo "Ошибка: неизвестное состояние регистрации времени.";
  exit;
}

if ($nextState != 1) {
  if ($transitionAction === WORKDAY_ACTION_UNDO_FINISH_LUNCH) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 4);

    $affectedRows = db_execute_affected_rows($link, "
      UPDATE visiting
      SET eat_stop_dt = '0000-00-00 00:00:00',
          out_dt = '0000-00-00 00:00:00',
          state = 3
      WHERE user_id = ?
        AND ID = ?
    ", 'ii', array($id, $ss_visiting_ID));

    if ($affectedRows === false) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if ($affectedRows <= 0) {
      echo "Ошибка: не удалось выполнить откат состояния. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = $transitionState;

    echo "1";
    exit;
  }

  if ($transitionAction === WORKDAY_ACTION_UNDO_START_LUNCH) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 3);

    $affectedRows = db_execute_affected_rows($link, "
      UPDATE visiting
      SET eat_start_dt = '0000-00-00 00:00:00',
          eat_stop_dt = '0000-00-00 00:00:00',
          state = 2
      WHERE user_id = ?
        AND ID = ?
    ", 'ii', array($id, $ss_visiting_ID));

    if ($affectedRows === false) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if ($affectedRows <= 0) {
      echo "Ошибка: не удалось выполнить откат состояния. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = $transitionState;

    echo "1";
    exit;
  }

  if ($transitionAction === WORKDAY_ACTION_UNDO_ARRIVE) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 2);

    $affectedRows = db_execute_affected_rows($link, "
      DELETE FROM visiting
      WHERE user_id = ?
        AND ID = ?
    ", 'ii', array($id, $ss_visiting_ID));

    if ($affectedRows === false) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if ($affectedRows <= 0) {
      echo "Ошибка: не удалось удалить приход. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = $transitionState;
    $_SESSION['ss_visiting_ID'] = 0;

    echo "1";
    exit;
  }

  if ($transitionAction === WORKDAY_ACTION_NOOP) {
    $_SESSION['ss_visiting_ID'] = 0;
    echo "1";
    exit;
  }

  if ($transitionAction === WORKDAY_ACTION_UNDO_LEAVE) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 0);

    $affectedRows = db_execute_affected_rows($link, "
      UPDATE visiting
      SET out_dt = '0000-00-00 00:00:00',
          state = 4
      WHERE user_id = ?
        AND ID = ?
    ", 'ii', array($id, $ss_visiting_ID));

    if ($affectedRows === false) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if ($affectedRows <= 0) {
      echo "Ошибка: не удалось выполнить откат ухода. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = $transitionState;

    echo "1";
    exit;
  }

  echo "Ошибка: неизвестное состояние регистрации времени.";
  exit;
}
?>
