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

mysqli_set_charset($link, "utf8");

$nextState = (int)$_POST['next'];

$dtResult = get_current_datetime_in_timezone();
$dateTimeStr = $dtResult[1];

$id = $_SESSION['ss_id'];

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
  $userID = mysqli_real_escape_string($link, $userID);
  $startDTStr = mysqli_real_escape_string($link, $startDTStr);
  $stopDTStr = mysqli_real_escape_string($link, $stopDTStr);
  $dateTimeStr = mysqli_real_escape_string($link, $dateTimeStr);

  $query = mysqli_query($link, "
    SELECT ID, state
    FROM visiting
    WHERE user_id = '$userID'
      AND (
        (
          in_dt >= '$startDTStr'
          AND in_dt < '$stopDTStr'
        )
        OR
        (
          state != 0
          AND in_dt < '$startDTStr'
          AND TIMESTAMPDIFF(SECOND, '$startDTStr', '$dateTimeStr') <= $maxOpenShiftSeconds
        )
      )
    ORDER BY in_dt DESC, ID DESC
    LIMIT 1
  ");

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

  $userID = mysqli_real_escape_string($link, $userID);
  $visitID = (int)$visitID;

  $query = mysqli_query($link, "
    SELECT ID, user_id, in_dt, eat_start_dt, eat_stop_dt, out_dt, state
    FROM visiting
    WHERE ID = '$visitID'
      AND user_id = '$userID'
    LIMIT 1
  ");

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

error_log(
  "TORI_SWITCH_SYNC user=$id next=$nextState state=$ss_state visit=$ss_visiting_ID start=$startDTStr stop=$stopDTStr now=$dateTimeStr"
);

if ($nextState == 1) {
  if ($ss_state == 1) {
    $_SESSION['ss_visiting_ID'] = 0;
    $ss_visiting_ID = 0;

    $openCheck = mysqli_query($link, "
      SELECT ID, in_dt, state
      FROM visiting
      WHERE user_id = '$id'
        AND state != 0
        AND (
          (
            in_dt >= '$startDTStr'
            AND in_dt < '$stopDTStr'
          )
          OR
          (
            in_dt < '$startDTStr'
            AND TIMESTAMPDIFF(SECOND, '$startDTStr', '$dateTimeStr') <= $maxOpenShiftSeconds
          )
        )
      ORDER BY in_dt DESC, ID DESC
      LIMIT 1
    ");

    if (!$openCheck) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if (mysqli_num_rows($openCheck) > 0) {
      $openRow = mysqli_fetch_array($openCheck, MYSQLI_ASSOC);

      $_SESSION['ss_state'] = (int)$openRow["state"];
      $_SESSION['ss_visiting_ID'] = (int)$openRow["ID"];

      error_log(
        "TORI_SWITCH_BLOCK_INSERT_RECENT user=$id open_visit=" . $openRow["ID"] . " open_state=" . $openRow["state"] . " open_in=" . $openRow["in_dt"]
      );

      echo "Ошибка: у сотрудника уже есть открытый рабочий день от " . $openRow["in_dt"] . ". Новый приход не создан. Обновите страницу.";
      exit;
    }

    $query = mysqli_query($link, "SELECT a.ID FROM visiting a WHERE a.ID = (SELECT max(ID) FROM visiting)");

    if (!$query) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    $newID = 1;
    $vn = mysqli_num_rows($query);

    if ($vn != 0) {
      $row = mysqli_fetch_array($query, MYSQLI_ASSOC);
      $newID = (int)$row["ID"] + 1;
    }

    error_log(
  "TORI_SWITCH_INSERT user=$id next=$nextState state=$ss_state visit=$ss_visiting_ID now=$dateTimeStr"
);

    $res = mysqli_query($link, "
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
        '$newID',
        '$id',
        '$dateTimeStr',
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        '0000-00-00 00:00:00',
        '2',
        b.RemoteWork,
        b.dayTransitionTime
      FROM employees b
      WHERE b.ID = '$id'
    ");

    if (!$res) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    $_SESSION['ss_state'] = 2;
    $_SESSION['ss_visiting_ID'] = $newID;

    echo "1";
    exit;
  }

  error_log(
  "TORI_SWITCH_LUNCH_START user=$id next=$nextState state=$ss_state visit=$ss_visiting_ID now=$dateTimeStr"
);

  if ($ss_state == 2) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 2);

    if (strtotime($dateTimeStr) <= strtotime($visitRow["in_dt"])) {
      echo "Ошибка: время начала обеда не может быть меньше или равно времени прихода.";
      exit;
    }

    $res = mysqli_query($link, "
      UPDATE visiting
      SET eat_start_dt = '$dateTimeStr',
          state = 3
      WHERE user_id = '$id'
        AND ID = '$ss_visiting_ID'
    ");

    if (!$res) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if (mysqli_affected_rows($link) <= 0) {
      echo "Ошибка: не удалось начать обед. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = 3;

    echo "1";
    exit;
  }

  if ($ss_state == 3) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 3);

    if ($visitRow["eat_start_dt"] == "0000-00-00 00:00:00") {
      echo "Ошибка: нельзя завершить обед, потому что время начала обеда не найдено.";
      exit;
    }

    if (strtotime($dateTimeStr) <= strtotime($visitRow["eat_start_dt"])) {
      echo "Ошибка: время окончания обеда не может быть меньше или равно времени начала обеда.";
      exit;
    }

    $res = mysqli_query($link, "
      UPDATE visiting
      SET eat_stop_dt = '$dateTimeStr',
          state = 4
      WHERE user_id = '$id'
        AND ID = '$ss_visiting_ID'
    ");

    if (!$res) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if (mysqli_affected_rows($link) <= 0) {
      echo "Ошибка: не удалось завершить обед. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = 4;

    echo "1";
    exit;
  }

  if ($ss_state == 4) {
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

    $dateTimeStrEsc = mysqli_real_escape_string($link, $dateTimeStr);
    $idEsc = mysqli_real_escape_string($link, $id);
    $visitIDEsc = mysqli_real_escape_string($link, $visitID);

    $res = mysqli_query($link, "
      UPDATE visiting
      SET out_dt = '$dateTimeStrEsc',
          state = 0
      WHERE user_id = '$idEsc'
        AND ID = '$visitIDEsc'
        AND state = 4
    ");

    if (!$res) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    $res2 = mysqli_query($link, "
      UPDATE remote_work
      SET stop_dt = NOW()
      WHERE user_id = '$idEsc'
        AND stop_dt IS NULL
    ");

    if (!$res2) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if (mysqli_affected_rows($link) <= 0) {
      $checkQuery = mysqli_query($link, "
        SELECT ID, state, out_dt
        FROM visiting
        WHERE user_id = '$idEsc'
          AND ID = '$visitIDEsc'
        LIMIT 1
      ");

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

    $_SESSION['ss_state'] = 0;
    $_SESSION['ss_visiting_ID'] = $visitID;

    echo "1";
    exit;
  }

  echo "Ошибка: неизвестное состояние регистрации времени.";
  exit;
}

if ($nextState != 1) {
  if ($ss_state == 4) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 4);

    $res = mysqli_query($link, "
      UPDATE visiting
      SET eat_stop_dt = '0000-00-00 00:00:00',
          out_dt = '0000-00-00 00:00:00',
          state = 3
      WHERE user_id = '$id'
        AND ID = '$ss_visiting_ID'
    ");

    if (!$res) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if (mysqli_affected_rows($link) <= 0) {
      echo "Ошибка: не удалось выполнить откат состояния. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = 3;

    echo "1";
    exit;
  }

  if ($ss_state == 3) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 3);

    $res = mysqli_query($link, "
      UPDATE visiting
      SET eat_start_dt = '0000-00-00 00:00:00',
          eat_stop_dt = '0000-00-00 00:00:00',
          state = 2
      WHERE user_id = '$id'
        AND ID = '$ss_visiting_ID'
    ");

    if (!$res) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if (mysqli_affected_rows($link) <= 0) {
      echo "Ошибка: не удалось выполнить откат состояния. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = 2;

    echo "1";
    exit;
  }

  if ($ss_state == 2) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 2);

    $res = mysqli_query($link, "
      DELETE FROM visiting
      WHERE user_id = '$id'
        AND ID = '$ss_visiting_ID'
    ");

    if (!$res) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if (mysqli_affected_rows($link) <= 0) {
      echo "Ошибка: не удалось удалить приход. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = 1;
    $_SESSION['ss_visiting_ID'] = 0;

    echo "1";
    exit;
  }

  if ($ss_state == 1) {
    $_SESSION['ss_visiting_ID'] = 0;
    echo "1";
    exit;
  }

  if ($ss_state == 0) {
    $visitRow = require_current_visit_row($link, $id, $ss_visiting_ID, $startDTStr, $stopDTStr, $dateTimeStr, $maxOpenShiftSeconds, 0);

    $res = mysqli_query($link, "
      UPDATE visiting
      SET out_dt = '0000-00-00 00:00:00',
          state = 4
      WHERE user_id = '$id'
        AND ID = '$ss_visiting_ID'
    ");

    if (!$res) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
      exit;
    }

    if (mysqli_affected_rows($link) <= 0) {
      echo "Ошибка: не удалось выполнить откат ухода. Обновите страницу.";
      exit;
    }

    $_SESSION['ss_state'] = 4;

    echo "1";
    exit;
  }

  echo "Ошибка: неизвестное состояние регистрации времени.";
  exit;
}
?>