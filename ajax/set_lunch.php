<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/html; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['ss_id']) || !isset($_SESSION['ss_visiting_ID'])) {
  exit('');
}

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID = (int)$_SESSION['ss_id'];
$visitingID = (int)$_SESSION['ss_visiting_ID'];

mysqli_set_charset($link, "utf8");

if ($visitingID <= 0) {
  exit('');
}

$userDayTransitionTime = isset($_SESSION['ss_dayTransitionTime'])
  ? $_SESSION['ss_dayTransitionTime']
  : "06:00:00";

$dtResult = get_current_datetime_in_timezone();
$currentDateTime = $dtResult[1];

$dateArr = datetimestr_to_day_start_stop_DT_ex_str_idx($currentDateTime, $userDayTransitionTime);

$startDTStr = $dateArr[0];
$stopDTStr = $dateArr[1];

$maxOpenShiftHours = 3;
$maxOpenShiftSeconds = $maxOpenShiftHours * 60 * 60;

$query = db_query($link, "
  SELECT ID, in_dt, eat_start_dt, eat_stop_dt, state
  FROM visiting
  WHERE ID = ?
    AND user_id = ?
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
  LIMIT 1
 ", 'iisssssi', array(
  $visitingID,
  $userID,
  $startDTStr,
  $stopDTStr,
  $startDTStr,
  $startDTStr,
  $currentDateTime,
  $maxOpenShiftSeconds
));

if (!$query) {
  exit('');
}

if (mysqli_num_rows($query) == 0) {
  $_SESSION['ss_state'] = 1;
  $_SESSION['ss_visiting_ID'] = 0;
  exit('');
}

$row = mysqli_fetch_assoc($query);

$eatStart = $row['eat_start_dt'];
$eatStop = $row['eat_stop_dt'];
$state = (int)$row['state'];

if ($state != 3 || $eatStart == '0000-00-00 00:00:00' || strtotime($eatStart) === false) {
  $_SESSION['ss_state'] = $state;
  $_SESSION['ss_visiting_ID'] = (int)$row["ID"];
  exit('');
}

$eatStartTimestamp = strtotime($eatStart);
$currentTimestamp = strtotime($currentDateTime);

if ($currentTimestamp === false || $eatStartTimestamp === false || $currentTimestamp < $eatStartTimestamp) {
  exit('');
}

$duration = $currentTimestamp - $eatStartTimestamp;
$durationStr = format_time_d_hhmmss_pure($duration);
?>
<table id="lunchPauseFullScreen" class="pause-overlay-table">
  <tr>
    <td class="pause-overlay-cell">
      <table class="add_time lunch-state-card">
        <tr>
          <td class="lunch-state-title">
            <div id="lunch_head_block">
              <div class="left_button lunch-state-back">
                <button id="lunch_time_back" class="time-state-icon-button" title="Возврат состояния регистрации времени до предыдущего" onclick="rollback_state();"><img src="img/rollbackState.png" alt=""></button>
              </div>
              <h5 class="bigbig1 lunch-state-heading"><br>Сотрудник на обеде<br><br></h5>
            </div>
          </td>
        </tr>
        <tr>
          <td class="report_no_padding_no_border">
            <table class="no_padding_real pause-state-details">
              <tr>
                <td class="report_no_padding pause-state-label lunch-state-label">
                  <h5 class="big">Время начала обеда:</h5>
                </td>
                <td class="report_no_padding pause-state-value">
                  <h5 class="big"><?= html_escape($eatStart) ?></h5>
                </td>
              </tr>
              <tr class="pause-state-row-alt">
                <td class="report_no_padding pause-state-label">
                  <h5 class="big">Длительность:</h5>
                </td>
                <td class="report_no_padding pause-state-value">
                  <h5 class="big" id="lunchDurationTimer"><?= html_escape($durationStr) ?></h5>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td class="report_no_padding pause-state-action-cell">
            <br>
            <button class="pause-state-action" onclick="reg_eat_stop();">
              Возобновить учет времени
            </button>
            <br><br>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<script type="text/javascript">
function set_pause_full_screen() {
  const el = document.getElementById('lunchPauseFullScreen');

  if (!el) {
    return;
  }

  el.style.position = 'fixed';
  el.style.top = '0';
  el.style.left = '0';
  el.style.width = window.innerWidth + 'px';
  el.style.height = window.innerHeight + 'px';
  el.style.zIndex = '9999';
  el.style.backgroundColor = 'rgba(255,255,255,0.96)';
}

function formatLunchDuration(totalSeconds) {
  totalSeconds = Math.max(0, parseInt(totalSeconds, 10) || 0);

  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;

  return String(hours).padStart(2, '0') + ':' +
    String(minutes).padStart(2, '0') + ':' +
    String(seconds).padStart(2, '0');
}

function startLunchDurationTimer(startTimestampMs, serverNowTimestampMs) {
  const timerEl = document.getElementById('lunchDurationTimer');

  if (!timerEl || !startTimestampMs || !serverNowTimestampMs) {
    return;
  }

  const browserStartedAtMs = Date.now();

  function updateLunchTimer() {
    const browserElapsedMs = Date.now() - browserStartedAtMs;
    const currentServerTimeMs = serverNowTimestampMs + browserElapsedMs;
    const durationSeconds = Math.floor((currentServerTimeMs - startTimestampMs) / 1000);

    timerEl.textContent = formatLunchDuration(durationSeconds);
  }

  updateLunchTimer();
  setInterval(updateLunchTimer, 1000);
}

set_pause_full_screen();
window.onresize = set_pause_full_screen;

startLunchDurationTimer(
  <?= (int)($eatStartTimestamp * 1000) ?>,
  <?= (int)($currentTimestamp * 1000) ?>
);
</script>
