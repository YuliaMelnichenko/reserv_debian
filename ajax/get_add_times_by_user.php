<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$_SESSION['add_time_page_mode'] = 2;

$userID = isset($_POST['user']) ? (int) $_POST['user'] : 0;

if ( $userID == -1 )
{ 
  $userID = isset($_SESSION['add_time_page_user_id'])
    ? (int) $_SESSION['add_time_page_user_id']
    : 0;
}

if ($userID <= 0) {
  deny_ajax_access(400, 'INVALID_USER');
}

require_ajax_self_or_superuser($userID);
$_SESSION['add_time_page_user_id'] = $userID;

$userName = get_user_name_by_id($userID);

echo "<table id=\"add_time_approvement_table\" class=\"notification-detail-header-table\">";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table class=\"notification-detail-header-table\">";
        echo "<tr>";
          echo "<td class=\"notification-detail-title-cell notification-detail-title-wide\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
          echo "<td class=\"notification-detail-back-cell\">";
            echo "<button class=\"journal-back-button\" title=\"Назад\" onclick=\"add_time_go_back();\"><h5>Назад</h5></button>";
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

      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Начало<br>(дата, время)</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Окончание<br>(дата, время)</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Длительность</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Основание</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Комментарий<br>работника</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Лицо,<br>принявшее решение</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Комментарий лица,<br>принявшего решение</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Статус</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Управление</h5></td>";
      echo "</tr>";
  
      $colorMode = 1;
      $color1 = "#ddffff";
      $color3 = "#ffffff";

      $addTimeInfo = get_all_add_work_info_by_user( $userID );

      for ( $idx = 0; $idx < count( $addTimeInfo ); $idx ++ )
      {
        $addInf = $addTimeInfo[$idx];

        $ta_id = (int)$addInf[8];
        $ta_start_dt = $addInf[0];
        $ta_stop_dt = $addInf[1];
        $ta_duration = $addInf[6];

        $ta_reason_description = $addInf[11];
        $ta_description = $addInf[3];
        $ta_SUdescription = $addInf[10];
        $ta_approved = $addInf[4];
        $ta_superuser = $addInf[5];
        
        $superUserName = get_superuser_name_by_id( $ta_superuser );

        if ( $ta_approved == 0 )
        { 
          $approvedStr = journal_status_label("на рассмотрении");
        }
        else if ( $ta_approved == 1 )
        { 
          $approvedStr = journal_status_label("принято");
        }   
        else if ( $ta_approved == -1 )
        { 
          $approvedStr = journal_status_label("отклонено");
        }
        else if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 )
        { 
          $approvedStr = journal_status_label("удалено");
        }

        $time_duration = $ta_duration > 0 ? format_time_( $ta_duration ) : "";

        if ( $colorMode == 0 )
        {
          $color = $color1;
          $colorMode = 1;
        }
        else
        {
          $color = $color3;
          $colorMode = 0;
        }

        $statusClass = "";
        $accBtnDisabled = "";
        $refBtnDisabled = "";

        $accBtnImg = "accept_small.bmp";
        $refBtnImg = "refuse_small.bmp";

        if ( $ta_approved == 0 )
        {
          $delRestore = "1";  
        }
        else if ( $ta_approved == 1 )
        {
          $accBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $delRestore = "1";
          $statusClass = "notification-status-accepted";
        }
        else if ( $ta_approved == -1 )
        {
          $refBtnDisabled = "disabled";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "1";
          $statusClass = "notification-status-refused";
        }
        else if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 )
        {
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
        echo "<td class=\"add_time notification-detail-duration-cell\"><h5 class=\"small\">" . html_escape($time_duration) . "</h5></td>";
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
                if ( $delRestore == 1 )
                { 
                  echo "<button class=\"journal-icon-button\" onclick=\"mark_as_deleted_add_time_for_user(" . (int) $ta_id . ");\">";
                    echo "<img title=\"Удалить\" src=\"img/delete_small.bmp\">";                   
                  echo "</button>";
                }
                else
                {
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
?>
