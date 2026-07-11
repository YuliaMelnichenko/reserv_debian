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
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body class=\"app-page accounting-errors-page\">";
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
list($accountingErrorsStartDate, $accountingErrorsStopDate) = accounting_errors_get_range($depthDays);
$accountingErrorsPeriodLabel = get_accounting_errors_period_label();
$backUrl = "accounting_errors_approvement.php";

sync_accounting_errors_for_user($link, $userID, $depthDays);

$userName = html_escape(get_user_name_by_id($userID));

echo "<div class=\"accounting-errors-layout\">";

echo "<input id=\"accountingErrorIDTemp\" type=\"hidden\" value=\"\">";
echo "<input id=\"accountingErrorActionTemp\" type=\"hidden\" value=\"\">";

echo "<table class=\"accounting-errors-page-table\">";
  echo "<tr>";
    echo "<td class=\"accounting-errors-nav-cell\">";
      include_once __DIR__ . "/navigate.php";
    echo "</td>";

    echo "<td class=\"accounting-errors-content-cell accounting-errors-content-cell-wide\">";

      echo "<div id=\"accountingErrorsUserHeader\">";
        echo "<h5 class=\"dark\"><br>/ошибки учета сотрудника<br><br></h5>";
      echo "</div>";

      echo "<h5 class=\"big\">Сотрудник: $userName</h5>";

      echo "<h5 class=\"big\">Текущий квартал: $accountingErrorsPeriodLabel</h5>";

      echo "<button class=\"button_style journal-back-button\" onclick=\"location.href='$backUrl'\">Назад</button>";

      echo "<div id=\"accountingErrorsUserTableScroll\">";
        echo "<table class=\"add_time\" id=\"accounting_errors_user_table\">";
          echo "<tr class=\"accounting-errors-table-head\">";
            echo "<td class=\"add_time accounting-errors-user-date-cell\"><h5 class=\"big\">Дата</h5></td>";
            echo "<td class=\"add_time accounting-errors-user-status-cell\"><h5 class=\"big\">Статус</h5></td>";
            echo "<td class=\"add_time accounting-errors-user-comment-cell\"><h5 class=\"big\">Комментарий сотрудника</h5></td>";
            echo "<td class=\"add_time accounting-errors-supervisor-comment-cell\"><h5 class=\"big\">Комментарий руководителя</h5></td>";
            echo "<td class=\"add_time accounting-errors-user-actions-cell\"><h5 class=\"big\">Действия</h5></td>";
          echo "</tr>";

          $query = db_query(
            $link,
            'SELECT ID, ERROR_DATE, STATUS, USER_COMMENT, SUPERVISOR_COMMENT, SUPERVISORID, USER_REPLY_DT, SUPERVISOR_REPLY_DT
             FROM accounting_errors
             WHERE USERID = ? AND USERID NOT IN (156, 161) AND ERROR_DATE >= ? AND ERROR_DATE <= ?
             ORDER BY ERROR_DATE DESC',
            'iss',
            array($userID, $accountingErrorsStartDate, $accountingErrorsStopDate)
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

              $rowClass = $color == "#ddffff" ? "accounting-errors-row-alt" : "accounting-errors-row";

              echo "<tr class=\"$rowClass\">";
                echo "<td class=\"add_time accounting-errors-user-date-cell\"><h5 class=\"middle\">$dateView</h5></td>";
                echo "<td class=\"add_time accounting-errors-user-status-cell\"><h5 class=\"$statusClass\">$statusName</h5></td>";
                echo "<td class=\"add_time accounting-errors-user-comment-cell\"><h5 class=\"middle\">$userCommentView</h5></td>";
                echo "<td class=\"add_time accounting-errors-supervisor-comment-cell\"><h5 class=\"middle\">$supervisorCommentView</h5></td>";

                echo "<td class=\"add_time accounting-errors-user-actions-cell\">";

                $acceptImg = "accept_small.bmp";
                $refuseImg = "refuse_small.bmp";
                $deleteImg = "delete_small.bmp";

                $acceptDisabledImg = "acceptDis_small.bmp";
                $refuseDisabledImg = "refuseDis_small.bmp";
                $deleteDisabledImg = "close.png";

                if ($status == 2) {
                    echo "<button class=\"journal-icon-button journal-icon-button-spaced journal-icon-button-disabled\" title=\"Уже принято\" disabled>";
                    echo "<img class=\"accounting-errors-action-icon\" src=\"img/$acceptDisabledImg\" onerror=\"this.src='img/$acceptImg';\" alt=\"Уже принято\">";
                    echo "</button>";
                }
                else if ($status == 4) {
                    echo "<button class=\"journal-icon-button journal-icon-button-spaced journal-icon-button-disabled\" title=\"Принять нельзя: запись удалена\" disabled>";
                    echo "<img class=\"accounting-errors-action-icon\" src=\"img/$acceptDisabledImg\" onerror=\"this.src='img/$acceptImg';\" alt=\"Принять нельзя\">";
                    echo "</button>";
                }
                else {
                    echo "<button class=\"journal-icon-button journal-icon-button-spaced\" title=\"Принять\" onclick=\"openAccountingErrorStatusWindow($errorID, 2, '$dateView');\">";
                    echo "<img class=\"accounting-errors-action-icon\" src=\"img/$acceptImg\" onerror=\"this.src='img/workTimeGood.png';\" alt=\"Принять\">";
                    echo "</button>";
                }

                if ($status == 3) {
                    echo "<button class=\"journal-icon-button journal-icon-button-spaced journal-icon-button-disabled\" title=\"Уже отклонено\" disabled>";
                    echo "<img class=\"accounting-errors-action-icon\" src=\"img/$refuseDisabledImg\" onerror=\"this.src='img/$refuseImg';\" alt=\"Уже отклонено\">";
                    echo "</button>";
                }
                else if ($status == 4) {
                    echo "<button class=\"journal-icon-button journal-icon-button-spaced journal-icon-button-disabled\" title=\"Отклонить нельзя: запись удалена\" disabled>";
                    echo "<img class=\"accounting-errors-action-icon\" src=\"img/$refuseDisabledImg\" onerror=\"this.src='img/$refuseImg';\" alt=\"Отклонить нельзя\">";
                    echo "</button>";
                }
                else {
                    echo "<button class=\"journal-icon-button journal-icon-button-spaced\" title=\"Отклонить\" onclick=\"openAccountingErrorStatusWindow($errorID, 3, '$dateView');\">";
                    echo "<img class=\"accounting-errors-action-icon\" src=\"img/$refuseImg\" onerror=\"this.src='img/workTimeBad.png';\" alt=\"Отклонить\">";
                    echo "</button>";
                }

                if ($status == 4) {
                    echo "<button class=\"journal-icon-button journal-icon-button-disabled\" title=\"Уже удалено\" disabled>";
                    echo "<img class=\"accounting-errors-action-icon\" src=\"img/$deleteDisabledImg\" onerror=\"this.src='img/$deleteImg';\" alt=\"Уже удалено\">";
                    echo "</button>";
                }
                else {
                    echo "<button class=\"journal-icon-button\" title=\"Удалить\" onclick=\"openAccountingErrorStatusWindow($errorID, 4, '$dateView');\">";
                    echo "<img class=\"accounting-errors-action-icon\" src=\"img/$deleteImg\" onerror=\"this.src='img/del.png';\" alt=\"Удалить\">";
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
              echo "<tr class=\"accounting-errors-row\">";
                echo "<td class=\"add_time accounting-errors-empty-cell\" colspan=5><h5 class=\"middle\">Ошибок учета нет</h5></td>";
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

<div id="accountingErrorStatusModalOverlay" class="accounting-error-modal-hidden">
  <div id="accountingErrorStatusModalWindow">
    <div id="accountingErrorStatusModalHeader">
      <span id="accountingErrorStatusModalTitle">Решение по ошибке учета</span>
      <button type="button" onclick="closeAccountingErrorStatusWindow()">×</button>
    </div>

    <div id="accountingErrorStatusModalDate"></div>

    <div class="accounting-error-modal-label">Комментарий руководителя:</div>
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
