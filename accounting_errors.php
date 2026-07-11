<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
require_page_auth();
include_once __DIR__ . '/funcs.php';
include __DIR__ . '/php_tori/connect.php';
save_last_location('accounting_errors.php');
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body bgcolor=\"#ffffff\" >";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script>

<?php
$userID = $_SESSION['ss_id'];
$depthDays = get_accounting_errors_default_depth_days();
list($accountingErrorsStartDate, $accountingErrorsStopDate) = accounting_errors_get_range($depthDays);
$accountingErrorsPeriodLabel = get_accounting_errors_period_label();

$syncResult = sync_accounting_errors_for_user($link, $userID, $depthDays);

if ($syncResult !== false) {
  $_SESSION['accounting_errors_sync_date'] = date('Y-m-d');
}

echo "<div align=\"left\">";

echo "<input id=\"accountingErrorIDTemp\" type=\"hidden\" value=\"\">";
echo "<input id=\"accountingErrorDateTemp\" type=\"hidden\" value=\"\">";

echo "<table border=0>";
  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width=250>";
      include_once __DIR__ . "/navigate.php";
    echo "</td>";

    $wholeWidth = 835;

    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width=$wholeWidth>";

      echo "<div id=\"accountingErrorsHeader\">";
        echo "<h5 class=\"dark\"><br>/ошибки учета рабочего времени<br><br></h5>";
      echo "</div>";

      echo "<h5 class=\"big\">Текущий квартал: $accountingErrorsPeriodLabel</h5>";
      echo "<div id=\"accountingErrorsTableScroll\">";
        echo "<table class=\"add_time\" id=\"accounting_errors_table\" border=1>";
            echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=120><h5 class=\"big\">Дата</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=150><h5 class=\"big\">Статус</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=430><h5 class=\"big\">Комментарий</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=100><h5 class=\"big\">Действие</h5></td>";
            echo "</tr>";

            $query = db_query(
                $link,
                'SELECT ID, ERROR_DATE, STATUS, USER_COMMENT
                 FROM accounting_errors
                 WHERE USERID = ? AND USERID NOT IN (156, 161) AND ERROR_DATE >= ? AND ERROR_DATE <= ? AND STATUS IN (0, 1, 2, 3)
                 ORDER BY ERROR_DATE DESC',
                'iss',
                array((int)$userID, $accountingErrorsStartDate, $accountingErrorsStopDate)
            );

            if (!$query) {
                echo "<tr><td colspan=4><h5 class=\"middle\">Не удалось загрузить ошибки учета.</h5></td></tr>";
            }
            else {
                $color = "#ddffff";
                $rowCount = 0;

                while ($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                    $rowCount++;

                    $errorID = (int)$row["ID"];
                    $errorDate = $row["ERROR_DATE"];
                    $status = (int)$row["STATUS"];
                    $statusName = get_accounting_error_status_name($status);

                    $comment = $row["USER_COMMENT"];

                    if ($comment == "" || $comment === null) {
                        $commentView = "Нет комментария";
                    }
                    else {
                        $commentView = htmlspecialchars($comment, ENT_QUOTES, "UTF-8");
                    }

                    $dateView = date("d.m.Y", strtotime($errorDate));

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
                    echo "<td class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"middle\">$commentView</h5></td>";
                    echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">";

                        if ($status == 0 || $status == 1 || $status == 3) {
                            $commentAttr = htmlspecialchars((string)$comment, ENT_QUOTES, 'UTF-8');
                            echo "<button class=\"journal-cell-icon-button\" title=\"Внести комментарий\" data-comment=\"$commentAttr\" onclick=\"openAccountingErrorCommentWindow($errorID, '$dateView', this.dataset.comment);\">";
                                echo "<img src=\"img/red.png\" onerror=\"this.src='img/pen.png';\" alt=\"Комментарий\">";
                            echo "</button>";
                        }
                        else {
                            echo "<h5 class=\"middle\">---</h5>";
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
                    echo "<tr bgcolor=\"#ffffff\"><td class=\"add_time\" colspan=4 valign=\"middle\" align=\"center\"><h5 class=\"middle\">Ошибок учета нет</h5></td></tr>";
                }
            }

        echo "</table>";
      echo "</div>";

    echo "</td>";
  echo "</tr>";
echo "</table>";

echo "</div>";
?>

<div id="accountingErrorModalOverlay" class="accounting-error-modal-hidden">
  <div id="accountingErrorModalWindow">
    <div id="accountingErrorModalHeader">
      <span>Комментарий к ошибке учета</span>
      <button type="button" onclick="closeAccountingErrorCommentWindow()">×</button>
    </div>

    <div id="accountingErrorModalDate"></div>

    <textarea id="accountingErrorCommentText"></textarea>

    <div id="accountingErrorModalActions">
      <button type="button" onclick="saveAccountingErrorComment()">Сохранить</button>
      <button type="button" onclick="closeAccountingErrorCommentWindow()">Отмена</button>
    </div>
  </div>
</div>

<script type="text/javascript" src="js/tory.js"></script>
<script type="text/javascript" charset="utf-8">

function openAccountingErrorCommentWindow(errorID, errorDate, comment) {
  document.getElementById('accountingErrorIDTemp').value = errorID;
  document.getElementById('accountingErrorDateTemp').value = errorDate;
  document.getElementById('accountingErrorModalDate').innerHTML = 'Дата: ' + errorDate;
  document.getElementById('accountingErrorCommentText').value = comment || '';
  document.getElementById('accountingErrorModalOverlay').style.display = 'block';
}

function closeAccountingErrorCommentWindow() {
  document.getElementById('accountingErrorModalOverlay').style.display = 'none';
  document.getElementById('accountingErrorIDTemp').value = '';
  document.getElementById('accountingErrorDateTemp').value = '';
  document.getElementById('accountingErrorCommentText').value = '';
}

function saveAccountingErrorComment() {
  var errorID = document.getElementById('accountingErrorIDTemp').value;
  var comment = document.getElementById('accountingErrorCommentText').value;

  if (errorID == '') {
    alert('Не найдена запись ошибки учета.');
    return;
  }

  if (comment.trim() == '') {
    alert('Введите комментарий.');
    return;
  }

  $.post(
    'ajax/set_accounting_error_comment.php',
    {
      error_id: errorID,
      comment: comment
    },
    function(dat) {
      if (dat == 1 || dat == '1') {
        closeAccountingErrorCommentWindow();
        window.location = 'accounting_errors.php';
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
