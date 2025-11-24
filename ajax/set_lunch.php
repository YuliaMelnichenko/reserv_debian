<?php
session_start();

header("Content-type: text/html; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

if (!isset($_SESSION['ss_id']) || !isset($_SESSION['ss_visiting_ID'])) {
    exit('Ошибка: нет активной сессии пользователя.');
}

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID = $_SESSION['ss_id'];
$visitingID = $_SESSION['ss_visiting_ID'];

mysqli_set_charset($link, "utf8");
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');

// Получаем данные о текущем посещении
$query = mysqli_query($link, "
    SELECT in_dt, eat_start_dt, eat_stop_dt, state
    FROM visiting
    WHERE ID = '$visitingID' AND user_id = '$userID'
    LIMIT 1
");

if (!$query) {
    echo "Ошибка БД: " . mysqli_error($link);
    exit;
}

if (mysqli_num_rows($query) == 0) {
    echo "Нет активной записи посещения.";
    exit;
}

$row = mysqli_fetch_assoc($query);
$eatStart = $row['eat_start_dt'];
$eatStop = $row['eat_stop_dt'];
$state = (int)$row['state'];

$dtResult = get_current_datetime_in_timezone();
$currentDateTime = $dtResult[1];

// вычисляем длительность обеда, если начат, но не завершён
$duration = 0;
if ($eatStart != '0000-00-00 00:00:00' && $state == 3) {
    $duration = strtotime($currentDateTime) - strtotime($eatStart);
}
$durationStr = format_time_d_hhmmss_pure($duration);

// Выводим окно обеда
?>
<table bgcolor="#FFFFFF" id="lunchPauseFullScreen">
  <tr>
    <td align="center" valign="middle">
      <table class="add_time" border="0" bgcolor="#ddeeff">
        <tr>
          <td align="center" width="446">
            <div id="lunch_head_block">
                <div class="left_button" style="display: flex; align-items: center; margin-left: 2px">
                    <button id ="lunch_time_back" title="возврат состояния регистрации времени до предыдущего" style="font-size: 100%; width:40px; height:20px; background-color:#f8d888; border:1px solid #888888;" onclick="rollback_state(); location.reload();"><img src="img/rollbackState.png"></button>
                </div>
                <h5 class="bigbig1" style="margin-right: 135px"><br>Сотрудник на обеде<br><br></h5>
            </div>
          </td>
        </tr>
        <tr>
          <td class="report_no_padding_no_border">
            <table class="no_padding_real" width="450">
              <tr>
                <td class="report_no_padding" valign="middle" align="left" width="200">
                  <h5 class="big">Время начала обеда:</h5>
                </td>
                <td class="report_no_padding" valign="middle" align="left">
                  <h5 class="big"><?= htmlspecialchars($eatStart) ?></h5>
                </td>
              </tr>
              <tr bgcolor="#ffffff">
                <td class="report_no_padding" valign="middle" align="left">
                  <h5 class="big">Длительность:</h5>
                </td>
                <td class="report_no_padding" valign="middle" align="left">
                  <h5 class="big"><?= htmlspecialchars($durationStr) ?></h5>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td class="report_no_padding" valign="middle" align="center">
            <br>
            <button
              style="margin:0; padding:0; font-size: 100%; width:390px; height:30px; background-color:#f8d888; border:1px solid #888888;"
              onclick="reg_eat_stop();">
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
  if (!el) return;
  el.style.position = 'fixed';
  el.style.top = '0';
  el.style.left = '0';
  el.style.width = window.innerWidth + 'px';
  el.style.height = window.innerHeight + 'px';
  el.style.zIndex = '9999';
  el.style.backgroundColor = 'rgba(255,255,255,0.96)';
}
set_pause_full_screen();
window.onresize = set_pause_full_screen;
</script>