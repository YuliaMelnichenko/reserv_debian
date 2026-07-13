<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$_SESSION['delay_page_mode'] = 2;

$userID = isset($_POST['user']) ? (int) $_POST['user'] : 0;

if ( $userID == -1 )
{ 
  $userID = isset($_SESSION['delay_page_user_id'])
    ? (int) $_SESSION['delay_page_user_id']
    : 0;
}

if ($userID <= 0) {
  deny_ajax_access(400, 'INVALID_USER');
}

require_ajax_self_or_superuser($userID);
$_SESSION['delay_page_user_id'] = $userID;

$user_defaultStartTime = "10:00:00";
$user_allowedDelay = 30;

get_user_defStartTime_and_allowedDelay( $userID, $user_defaultStartTime, $user_allowedDelay );
$userName = get_user_name_by_id($userID);


echo "<table id=\"delay_approvement_table\" class=\"notification-detail-header-table\">";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table class=\"notification-detail-header-table\">";
        echo "<tr>";
          echo "<td class=\"notification-detail-title-cell notification-detail-title-delay\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
          echo "<td class=\"notification-detail-back-cell notification-detail-back-wide\">";
            echo "<button class=\"journal-back-button\" title=\"Назад\" onclick=\"delay_go_back();\"><h5>Назад</h5></button>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    echo "</td>";     
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"nopadding notification-detail-body-cell notification-detail-body-wide\">";

      echo "<table class=\"add_time notification-detail-table delay-detail-table\">";
      echo "<tr class=\"notification-detail-head\">";

      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Дата</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Время<br>прихода</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Длительность<br>опоздания</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Комментарий<br>работника</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>С кем предварительно<br>согласовано</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Лицо, принявшее<br>решение</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Комментарий лица,<br>принявшего решение</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Статус</h5></td>";
      echo "<td class=\"add_time notification-detail-head-cell\"><h5>Управление</h5></td>";
      echo "</tr>";
  
      $colorMode = 1;
      $color1 = "#ddffff";
      $color3 = "#ffffff";

      $delayTimes = Array();

      $delayTimes = get_all_delay_info_by_user( $userID, $user_defaultStartTime, $user_allowedDelay );

      foreach( $delayTimes as $delayTime )
      {
        $retDelay_id = (int)$delayTime[0];
        $retDelay_superuserID = $delayTime[1];
        $retDelay_agreed = $delayTime[2];
        $retDelay_description = $delayTime[3];
        $retDelay_penalty_id = $delayTime[4];
        $retDelay_acceptor_description = $delayTime[5];
        $retDelay_approved = $delayTime[6];
        $retDelay_duration = $delayTime[7];
        $retDelay_start_time = $delayTime[8];
        $retDelay_start_date = $delayTime[11];
        $retDelay_acceptorID = $delayTime[12];

        $superUserName = get_superuser_name_by_id( $retDelay_superuserID );  
        $acceptorName = get_superuser_name_by_id( $retDelay_acceptorID );  

        $statusClass = "";
        $agreedClass = "";
        $accBtnDisabled = "";
        $refBtnDisabled = "";

        if ( $retDelay_agreed == 0 )
        { 
          if ( $superUserName == "" )
          {
            $superUserName = "Ни с кем!";
          }
          $agreedClass = "notification-status-refused";
        }
        else if ( $retDelay_agreed == 1 )
        { 
          $agreedClass = "";
        }   

        $accBtnImg = "accept_small.bmp";
        $refBtnImg = "refuse_small.bmp";

        if ( $retDelay_approved == 0 )
        { 
          $content1 = journal_status_label("на рассмотрении");
          $delRestore = "1";  
        }
        else if ( $retDelay_approved == 1 )
        { 
          $content1 = journal_status_label("принято");
          $statusClass = "notification-status-accepted";
          $accBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $delRestore = "1";
        }   
        else if ( $retDelay_approved == -1 )
        { 
          $content1 = journal_status_label("отклонено");
          $statusClass = "notification-status-refused";
          $refBtnDisabled = "disabled";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "1";
        }
        else if ( $retDelay_approved == 99 OR $retDelay_approved == 100 OR $retDelay_approved == 101 )
        { 
          $content1 = journal_status_label("отклонено", "big");
          $statusClass = "notification-status-deleted";
          $accBtnDisabled = "disabled";
          $refBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "0";
        }

        $time_duration = format_time_d_hhmmss_pure( $retDelay_duration );
  	
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

        $rowClass = $color == $color1 ? "notification-detail-row-alt" : "notification-detail-row";

        echo "<tr class=\"$rowClass\">";
          echo "<td class=\"add_time notification-detail-short-date-cell\"><h5 class=\"small\">" . html_escape($retDelay_start_date) . "</h5></td>";
          echo "<td class=\"add_time notification-detail-date-cell\"><h5 class=\"small\">" . html_escape($retDelay_start_time) . "</h5></td>";
          echo "<td class=\"add_time notification-detail-duration-cell\"><h5 class=\"small\">" . html_escape($time_duration) . "</h5></td>";
          echo "<td class=\"add_time notification-detail-delay-comment-cell\"><h5 class=\"small\">" . html_escape($retDelay_description) . "</h5></td>";
          echo "<td class=\"add_time notification-detail-supervisor-wide-cell $agreedClass\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
          echo "<td class=\"add_time notification-detail-supervisor-wide-cell\"><h5 class=\"small\">" . html_escape($acceptorName) . "</h5></td>";
          echo "<td class=\"add_time notification-detail-delay-comment-cell\"><h5 class=\"small\">" . html_escape($retDelay_acceptor_description) . "</h5></td>";
          echo "<td class=\"add_time notification-detail-delay-status-cell $statusClass\">$content1</td>";

          echo "<td class=\"add_time notification-detail-actions-cell\">";
   
            echo "<table class=\"notification-detail-actions-table\">";
              echo "<tr>";
                echo "<td class=\"nopadding_s notification-detail-action-cell\">";
                  echo "<button class=\"journal-icon-button\" onclick=\"accept_delay_for_user(" . (int) $retDelay_id . ", " . html_escape(js_encode($retDelay_acceptor_description)) . ", " . (int) $retDelay_penalty_id . ", " . html_escape(js_encode($retDelay_start_date)) . ", " . (int) $userID . ");\" $accBtnDisabled>";
                    echo "<img title=\"Принять\" src=\"img/$accBtnImg\">";                   
                  echo "</button>";
                echo "</td>";
                echo "<td class=\"nopadding_s notification-detail-action-cell\">";
                  echo "<button class=\"journal-icon-button\" onclick=\"refuse_delay_for_user(" . (int) $retDelay_id . ", " . html_escape(js_encode($retDelay_acceptor_description)) . ", " . (int) $retDelay_penalty_id . ", " . html_escape(js_encode($retDelay_start_date)) . ", " . (int) $userID . ");\" $refBtnDisabled>";
                    echo "<img title=\"Отклонить\" src=\"img/$refBtnImg\">";                   
                  echo "</button>";
                echo "</td>";
                echo "<td class=\"nopadding_s notification-detail-action-cell\">";
                  if ( $delRestore == 1 )
                  { 
                    echo "<button class=\"journal-icon-button\" onclick=\"mark_as_deleted_delay_for_user(" . (int) $retDelay_id . ");\">";
                      echo "<img title=\"Удалить\" src=\"img/delete_small.bmp\">";                   
                    echo "</button>";
                  }
                  else
                  {
                    echo "<button class=\"journal-icon-button\" onclick=\"mark_as_undeleted_delay_for_user(" . (int) $retDelay_id . ");\">";
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
    echo "</td>";
  echo "</tr>";
echo "</table>";
?>
