<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
require_page_auth();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/style.css\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body bgcolor=\"#ffffff\" >";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script>

<?php
include_once __DIR__ . "/funcs.php";
save_last_location("accounting_errors_approvement.php");
include __DIR__ . "/php_tori/connect.php";

if (!isset($_GET["mid"])) {
  echo "<h5 class=\"big\">Ошибка: не передан сотрудник.</h5>";
  echo "</body>";
  echo "</html>";
  exit;
}

$mid = (string)$_GET["mid"];
$resArr = extractUidFromMaskedUID($mid);

if ($resArr[0] != 1) {
  echo "<h5 class=\"big\">Ошибка: некорректный идентификатор сотрудника.</h5>";
  echo "</body>";
  echo "</html>";
  exit;
}

$userID = (int)$resArr[1];
require_page_supervisor_for_user($userID, 3);

$depthDays = get_accounting_errors_default_depth_days();

sync_accounting_errors_for_user($link, $userID, $depthDays);

$userName = html_escape(get_user_name_by_id($userID));

echo "<div align=\"left\">";

echo "<input id=\"accountingErrorIDTemp\" type=\"hidden\" value=\"\">";
echo "<input id=\"accountingErrorActionTemp\" type=\"hidden\" value=\"\">";

echo "<table border=0>";
  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width=250>";
      include_once __DIR__ . "/navigate.php";
    echo "</td>";

    $wholeWidth = 950;

    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width=$wholeWidth>";

      echo "<div id=\"accountingErrorsUserHeader\">";
        echo "<h5 class=\"dark\"><br>/ошибки учета сотрудника<br><br></h5>";
      echo "</div>";

      echo "<h5 class=\"big\">Сотрудник: $userName</h5>";

      $today = date("d-m-Y");
      $dateForm = date("d.m.Y", strtotime("-$depthDays days"));

      echo "<h5 class=\"big\"> Глубина просмотра журнала ($depthDays дней): $dateForm - $today </h5>";

      echo "<button class=\"button_style\" style=\"font-size: 90%; width:90px; height:24px; background-color:#f8d888; border:1px solid #888888; margin-bottom:8px;\" onclick=\"location.href='accounting_errors_approvement.php'\">Назад</button>";

      echo "<div id=\"accountingErrorsUserTableScroll\">";
        echo "<table class=\"add_time\" id=\"accounting_errors_user_table\" border=1>";
          echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=95><h5 class=\"big\">Дата</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=125><h5 class=\"big\">Статус</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=330><h5 class=\"big\">Комментарий сотрудника</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=230><h5 class=\"big\">Комментарий руководителя</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=150><h5 class=\"big\">Действия</h5></td>";
          echo "</tr>";

          $startDate = date("Y-m-d", strtotime("-$depthDays days"));
          $query = db_query(
            $link,
            'SELECT ID, ERROR_DATE, STATUS, USER_COMMENT, SUPERVISOR_COMMENT, SUPERVISORID, USER_REPLY_DT, SUPERVISOR_REPLY_DT
             FROM accounting_errors
             WHERE USERID = ? AND ERROR_DATE >= ?
             ORDER BY ERROR_DATE DESC',
            'is',
            array($userID, $startDate)
          );

          if (!$query) {
            echo "<tr><td colspan=5><h5 class=\"middle\">Не удалось загрузить ошибки учета.</h5></td></tr>";
          }
          else {
            $color = "#ddffff";
            $rowCount = 0;

            while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
              $rowCount++;

              $errorID = (int)$row["ID"];
              $errorDate = $row["ERROR_DATE"];
              $dateView = date("d.m.Y", strtotime($errorDate));

              $status = (int)$row["STATUS"];
              $statusName = get_accounting_error_status_name($status);

              $userComment = $row["USER_COMMENT"];

              if ($userComment == "" || $userComment === null) {
                $userCommentView = "Нет комментария";
              }
              else {
                $userCommentView = nl2br(htmlspecialchars($userComment, ENT_QUOTES, "UTF-8"));
              }

              $supervisorComment = $row["SUPERVISOR_COMMENT"];

              if ($supervisorComment == "" || $supervisorComment === null) {
                $supervisorCommentView = "Нет комментария";
              }
              else {
                $supervisorCommentView = nl2br(htmlspecialchars($supervisorComment, ENT_QUOTES, "UTF-8"));
              }

              $statusClass = "middle";

              if ($status == 0) {
                $statusClass = "middleRed";
              }
              else if ($status == 1) {
                $statusClass = "middleBlue1";
              }
              else if ($status == 2) {
                $statusClass = "middleGreen";
              }
              else if ($status == 3) {
                $statusClass = "middleRed";
              }

              echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
                echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"middle\">$dateView</h5></td>";
                echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"$statusClass\">$statusName</h5></td>";
                echo "<td class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"middle\">$userCommentView</h5></td>";
                echo "<td class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"middle\">$supervisorCommentView</h5></td>";

                echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">";

                $acceptImg = "accept_small.bmp";
                $refuseImg = "refuse_small.bmp";
                $deleteImg = "delete_small.bmp";

                $acceptDisabledImg = "acceptDis_small.bmp";
                $refuseDisabledImg = "refuseDis_small.bmp";
                $deleteDisabledImg = "close.png";

                if ($status == 2) {
                    echo "<button title=\"Уже принято\" disabled style=\"padding:0px; background-color:#ffffff; border:0px solid #888888; cursor:default; margin-right:4px; opacity:0.8;\">";
                    echo "<img src=\"img/$acceptDisabledImg\" onerror=\"this.src='img/$acceptImg';\" alt=\"Уже принято\" height=\"14\">";
                    echo "</button>";
                }
                else if ($status == 4) {
                    echo "<button title=\"Принять нельзя: запись удалена\" disabled style=\"padding:0px; background-color:#ffffff; border:0px solid #888888; cursor:default; margin-right:4px; opacity:0.8;\">";
                    echo "<img src=\"img/$acceptDisabledImg\" onerror=\"this.src='img/$acceptImg';\" alt=\"Принять нельзя\" height=\"14\">";
                    echo "</button>";
                }
                else {
                    echo "<button title=\"Принять\" style=\"padding:0px; background-color:#ffffff; border:0px solid #888888; cursor:pointer; margin-right:4px;\" onclick=\"openAccountingErrorStatusWindow($errorID, 2, '$dateView');\">";
                    echo "<img src=\"img/$acceptImg\" onerror=\"this.src='img/workTimeGood.png';\" alt=\"Принять\" height=\"14\">";
                    echo "</button>";
                }

                if ($status == 3) {
                    echo "<button title=\"Уже отклонено\" disabled style=\"padding:0px; background-color:#ffffff; border:0px solid #888888; cursor:default; margin-right:4px; opacity:0.8;\">";
                    echo "<img src=\"img/$refuseDisabledImg\" onerror=\"this.src='img/$refuseImg';\" alt=\"Уже отклонено\" height=\"14\">";
                    echo "</button>";
                }
                else if ($status == 4) {
                    echo "<button title=\"Отклонить нельзя: запись удалена\" disabled style=\"padding:0px; background-color:#ffffff; border:0px solid #888888; cursor:default; margin-right:4px; opacity:0.8;\">";
                    echo "<img src=\"img/$refuseDisabledImg\" onerror=\"this.src='img/$refuseImg';\" alt=\"Отклонить нельзя\" height=\"14\">";
                    echo "</button>";
                }
                else {
                    echo "<button title=\"Отклонить\" style=\"padding:0px; background-color:#ffffff; border:0px solid #888888; cursor:pointer; margin-right:4px;\" onclick=\"openAccountingErrorStatusWindow($errorID, 3, '$dateView');\">";
                    echo "<img src=\"img/$refuseImg\" onerror=\"this.src='img/workTimeBad.png';\" alt=\"Отклонить\" height=\"14\">";
                    echo "</button>";
                }

                if ($status == 4) {
                    echo "<button title=\"Уже удалено\" disabled style=\"padding:0px; background-color:#ffffff; border:0px solid #888888; cursor:default; opacity:0.8;\">";
                    echo "<img src=\"img/$deleteDisabledImg\" onerror=\"this.src='img/$deleteImg';\" alt=\"Уже удалено\" height=\"14\">";
                    echo "</button>";
                }
                else {
                    echo "<button title=\"Удалить\" style=\"padding:0px; background-color:#ffffff; border:0px solid #888888; cursor:pointer;\" onclick=\"openAccountingErrorStatusWindow($errorID, 4, '$dateView');\">";
                    echo "<img src=\"img/$deleteImg\" onerror=\"this.src='img/del.png';\" alt=\"Удалить\" height=\"14\">";
                    echo "</button>";
                }

                echo "</td>";
                echo "</tr>";

              if ($color == "#ddffff") {
                $color = "#ffffff";
              }
              else {
                $color = "#ddffff";
              }
            }

            if ($rowCount == 0) {
              echo "<tr bgcolor=\"#ffffff\">";
                echo "<td class=\"add_time\" colspan=5 valign=\"middle\" align=\"center\"><h5 class=\"middle\">Ошибок учета нет</h5></td>";
              echo "</tr>";
            }
          }

        echo "</table>";
      echo "</div>";

    echo "</td>";
  echo "</tr>";
echo "</table>";

echo "</div>";
?>

<div id="accountingErrorStatusModalOverlay" style="display:none;">
  <div id="accountingErrorStatusModalWindow">
    <div id="accountingErrorStatusModalHeader">
      <span id="accountingErrorStatusModalTitle">Решение по ошибке учета</span>
      <button type="button" onclick="closeAccountingErrorStatusWindow()">×</button>
    </div>

    <div id="accountingErrorStatusModalDate"></div>

    <div style="margin-bottom: 6px;">Комментарий руководителя:</div>
    <textarea id="accountingErrorSupervisorCommentText"></textarea>

    <div id="accountingErrorStatusModalActions">
      <button type="button" onclick="saveAccountingErrorStatus()">Сохранить</button>
      <button type="button" onclick="closeAccountingErrorStatusWindow()">Отмена</button>
    </div>
  </div>
</div>

<script type="text/javascript" src="js/tory.js"></script>
<script type="text/javascript" charset="utf-8">

function getAccountingErrorActionName(action) {
  action = parseInt(action, 10);

  if (action === 2) {
    return 'Принять';
  }

  if (action === 3) {
    return 'Отклонить';
  }

  if (action === 4) {
    return 'Удалить';
  }

  return 'Изменить статус';
}

function openAccountingErrorStatusWindow(errorID, action, dateView) {
  document.getElementById('accountingErrorIDTemp').value = errorID;
  document.getElementById('accountingErrorActionTemp').value = action;
  document.getElementById('accountingErrorStatusModalTitle').innerHTML = getAccountingErrorActionName(action);
  document.getElementById('accountingErrorStatusModalDate').innerHTML = 'Дата: ' + dateView;
  document.getElementById('accountingErrorSupervisorCommentText').value = '';
  document.getElementById('accountingErrorStatusModalOverlay').style.display = 'block';
}

function closeAccountingErrorStatusWindow() {
  document.getElementById('accountingErrorStatusModalOverlay').style.display = 'none';
  document.getElementById('accountingErrorIDTemp').value = '';
  document.getElementById('accountingErrorActionTemp').value = '';
  document.getElementById('accountingErrorSupervisorCommentText').value = '';
}

function saveAccountingErrorStatus() {
  var errorID = document.getElementById('accountingErrorIDTemp').value;
  var action = document.getElementById('accountingErrorActionTemp').value;
  var comment = document.getElementById('accountingErrorSupervisorCommentText').value;

  if (errorID == '' || action == '') {
    alert('Не найдена запись ошибки учета.');
    return;
  }

  $.post(
    'ajax/set_accounting_error_status.php',
    {
      error_id: errorID,
      status: action,
      comment: comment
    },
    function(dat) {
      if (dat == 1 || dat == '1') {
        closeAccountingErrorStatusWindow();
        window.location = window.location.href;
      }
      else {
        alert(dat);
      }
    }
  );
}

function update_clock()
{
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat)
  {
    if (document.getElementById('dateTimeFieldNav'))
    {
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId = setInterval("update_clock()", 10000);

</script>

<?php
echo "</body>";
echo "</html>";
?>
