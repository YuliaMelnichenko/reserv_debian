<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
include_once __DIR__ . "/funcs.php";
require_once __DIR__ . "/inc/add_time_journal.php";
save_last_location( "time_approvement.php" );
$mid = (string) ($_GET['mid'] ?? '');

if ($mid === '') {
  header('Location: time_approvement.php');
  exit;
}

$resArr = extractUidFromMaskedUID($mid);
$uidValid = (int) $resArr[0];
$userID = (int) $resArr[1];

if ($uidValid === 0 || $userID <= 0) {
  header('Location: time_approvement.php');
  exit;
}

require_page_supervisor_for_user($userID, 0);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php
echo "<html>";
echo "<head>";
echo "<title>Система учета времени присутствия сотрудников ООО НПФ &quot;ТОРИ&quot;</title>";
echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body class=\"app-page\">";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" charset="utf-8"> 

function update_clock(){
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat) {
    if ( document.getElementById('dateTimeFieldNav') ){
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId = setInterval(update_clock, 10000);
</script>

<?php
echo "<div class=\"notification-page-layout\">";

include __DIR__ . "/php_tori/connect.php";

mysqli_set_charset($link, "utf8");

  echo "<table class=\"notification-page-table\">";
    echo "<tr>";
      echo "<td class=\"notification-nav-cell\">";
        include_once __DIR__ . "/navigate.php";
      echo "</td>";

      $wholeWidth = 1158;
      $wholeTableWidth = $wholeWidth;

      echo "<td id=\"add_time_content_width\" class=\"notification-content-cell notification-content-cell-approvement-wide\">";

        echo "<div id=\"addTimeHeader\">";
          echo "<h5 class=\"dark\"><br>/уведомления по работе вне офиса<br><br></h5>";
      echo "</div>";

      $backUrl = "time_approvement.php";
      $journal = get_add_time_journal_context($link, $userID, get_current_datetime_in_timezone_str(1, 0));

      if ($journal === false) {
        echo "<h5>" . html_escape(database_error_message($link, __FILE__ . ':' . __LINE__)) . "</h5>";
        exit;
      }

      if ($journal === null) {
        header('Location: time_approvement.php');
        exit;
      }

      $userName = $journal['user_name'];
      $addTimeInfo = $journal['entries'];

      if ( count( $addTimeInfo ) == 0 ){
        echo "<table id=\"add_time_approvement_table\" class=\"notification-detail-header-table\">";
          echo "<tr>";
            echo "<td class=\"notification-detail-title-cell notification-detail-title-wide\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
            echo "<td class=\"notification-detail-back-cell\">";
              echo "<button class=\"journal-back-button\" title=\"Назад\" onclick=\"location.href='$backUrl';\"><h5>Назад</h5></button>";
            echo "</td>";
          echo "</tr>";
        echo "</table>";
        echo "<h5><br>Нет сведений!</h5>";
        echo "</td>";               
        echo "<tr>";
        echo "<table>";
        exit;
      }

echo "<table id=\"add_time_approvement_table\" class=\"notification-detail-header-table\">";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table class=\"notification-detail-header-table\">";
        echo "<tr>";
          echo "<td class=\"notification-detail-title-cell notification-detail-title-wide\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
          echo "<td class=\"notification-detail-back-cell\">";
            echo "<button class=\"journal-back-button\" title=\"Назад\" onclick=\"location.href='$backUrl';\"><h5>Назад</h5></button>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"nopadding notification-detail-body-cell\">";

      echo "<div class=\"notification-table-scroll notification-table-scroll-full\">";
      echo "<table class=\"add_time notification-detail-table time-detail-table\">";
      echo "<tr class=\"notification-detail-head\">";

      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Начало<br>(дата, время)</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Окончание<br>(дата, время)</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Длительность</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Основание</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Комментарий<br>работника</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Лицо,<br>принявшее решение</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Комментарий лица,<br>принявшего решение</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Статус</h5>"."</td>";
      echo "<td class=\"add_time notification-detail-head-cell\">"."<h5>Управление</h5>"."</td>";
      echo "</tr>";
  
      $colorMode = 1;
      $color1 = "#ddffff";
      $color3 = "#ffffff";

      for ( $idx = 0; $idx < count( $addTimeInfo ); $idx ++ ){
        $addInf = $addTimeInfo[$idx];

        $ta_id = $addInf['id'];
        $ta_start_dt = $addInf['start_datetime'];
        $ta_stop_dt = $addInf['stop_datetime'];
        $ta_duration = $addInf['duration'];

        $ta_reason_description = $addInf['reason_description'];
        $ta_description = $addInf['employee_comment'];
        $ta_SUdescription = $addInf['decision_comment'];
        $ta_approved = $addInf['status'];
        $superUserName = $addInf['supervisor_name'];

        if ( $ta_approved == 0 ){
          $approvedStr = journal_status_label("на рассмотрении");
        }
        else if ( $ta_approved == 1 ){
          $approvedStr = journal_status_label("принято");
        }
        else if ( $ta_approved == -1 ){ 
          $approvedStr = journal_status_label("отклонено");
        }
        else if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 ){
          $approvedStr = journal_status_label("удалено");
        }

        $time_duration = $ta_duration > 0 ? format_time_( $ta_duration ) : "";

        if ( $colorMode == 0 ){
          $color = $color1;
          $colorMode = 1;
        }
        else{
          $color = $color3;
          $colorMode = 0;
        }

        $statusClass = "";
        $accBtnDisabled = "";
        $refBtnDisabled = "";

        $accBtnImg = "accept_small.bmp";
        $refBtnImg = "refuse_small.bmp";

        if ( $ta_approved == 0 ){
          $delRestore = "1";  
        }
        else if ( $ta_approved == 1 ){
          $accBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $delRestore = "1";
          $statusClass = "notification-status-accepted";
        }
        else if ( $ta_approved == -1 ){
          $refBtnDisabled = "disabled";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "1";
          $statusClass = "notification-status-refused";
        }
        else if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 ){
          $accBtnDisabled = "disabled";
          $refBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "0";
          $statusClass = "notification-status-deleted";
        }

        $rowClass = $color == $color1 ? "notification-detail-row-alt" : "notification-detail-row";

        echo "<tr class=\"$rowClass\">";
        echo "<td class=\"add_time notification-detail-date-cell\"><h5 class=\"small\">" . html_escape($ta_start_dt) . "</h5></td>";
        echo "<td class=\"add_time notification-detail-date-cell\"><h5 class=\"small\">" . html_escape($ta_stop_dt) . "</h5></td>";
        echo "<td class=\"add_time notification-detail-duration-cell\"><h5 class=\"small\">".$time_duration."</h5></td>";
        echo "<td class=\"add_time notification-detail-reason-cell\"><h5 class=\"small\">" . html_escape($ta_reason_description) . "</h5></td>";
        echo "<td class=\"add_time notification-detail-comment-cell\"><h5 class=\"small\">" . html_escape($ta_description) . "</h5></td>";
        echo "<td class=\"add_time notification-detail-supervisor-wide-cell\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
        echo "<td class=\"add_time notification-detail-comment-cell\"><h5 class=\"small\">" . html_escape($ta_SUdescription) . "</h5></td>";
        echo "<td class=\"add_time notification-detail-status-cell $statusClass\">$approvedStr</td>";
        echo "<td class=\"add_time notification-detail-actions-cell\">";

          echo "<table class=\"notification-detail-actions-table\">";
            echo "<tr>";
              echo "<td class=\"nopadding_s notification-detail-action-cell\">";
                echo "<button class=\"journal-icon-button\" onclick=\"accept_add_time_for_user(" . (int) $ta_id . ", " . html_escape(js_encode($ta_SUdescription)) . ");\" $accBtnDisabled>";
                  echo "<img title=\"Принять\" src=\"img/$accBtnImg\">";
                echo "</button>";
              echo "</td>";
              echo "<td class=\"nopadding_s notification-detail-action-cell\">";
                echo "<button class=\"journal-icon-button\" onclick=\"refuse_add_time_for_user(" . (int) $ta_id . ", " . html_escape(js_encode($ta_SUdescription)) . ");\" $refBtnDisabled>";
                  echo "<img title=\"Отклонить\" src=\"img/$refBtnImg\">";
                echo "</button>";
              echo "</td>";
              echo "<td class=\"nopadding_s notification-detail-action-cell\">";
                if ( $delRestore == 1 ){
                  echo "<button class=\"journal-icon-button\" onclick=\"mark_as_deleted_add_time_for_user(" . (int) $ta_id . ");\">";
                    echo "<img title=\"Удалить\" src=\"img/delete_small.bmp\">";
                  echo "</button>";
                }
                else{
                  echo "<button class=\"journal-icon-button\" onclick=\"mark_as_undeleted_add_time_for_user(" . (int) $ta_id . ");\">";
                    echo "<img title=\"Восстановить\" src=\"img/restore_small.bmp\">";
                  echo "</button>";
                }
              echo "</td>";
            echo "</tr>";
          echo "</table>";

        echo "</td>";
        echo "</tr>";
      }

      echo "</table>";
      echo "</div>";
    echo "</td>";
  echo "</tr>";
echo "</table>";
      echo "</td>";
    echo "</tr>";
  echo "</table>";
echo "</div>";

echo "<input id=\"recIDTempVal\" type=\"hidden\" value=\"\">";
echo "<input id=\"acceptTempVal\" type=\"hidden\" value=\"\">";

      echo "<div id=\"add_time_approvement_desc\">";
      echo "<div class=\"comment\">";
        echo "<h5 class=\"bigbig\">Комментарий</h5>";
      echo "</div>";
      echo "<div class=\"text_box\">";
        echo "<textarea id=\"add_time_part_desc_2\" class=\"journal-comment-textarea\" cols=\"43\" rows=\"3\"></textarea>";
      echo "</div>";
      echo "<div class=\"box_btn\">";
        echo "<div>";
        echo "<button class=\"journal-modal-action-button journal-modal-action-cancel\" onclick=\"document.getElementById('add_time_approvement_desc').style.display='none';\">Отмена</button>";
        echo "</div>";
        echo "<div>";
        echo "<button class=\"journal-modal-action-button journal-modal-action-save\" onclick=\"accept_refuse_add_time_for_user_final( document.getElementById('recIDTempVal').value, document.getElementById('add_time_part_desc_2').value, document.getElementById('acceptTempVal').value );\">Сохранить</button>";
        echo "</div>";
      echo "</div>";
    echo "</div>";
?>

<script type="text/javascript" src="js/tory.js"></script>

<?php
echo "</body>";
echo "</html>";
?>
