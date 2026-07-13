<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

$content = "<h5 class=\"big\">Работа вне офиса. Внесение сведений</h5><br>";

$userIDtoShow = -1;

if ( isset( $_SESSION['ss_id'] ) )
{
  $userIDtoShow = $_SESSION['ss_id']; 
}

$content .= "<br><table class=\"add-time-list-actions\">";
$content .= "<tr>";

$content .= "<td class=\"add-time-list-action-left\">";
$content .= "<button class=\"journal-action-button journal-action-button-wide journal-action-button-close\" onclick=\"cancel_time_add(); location.reload();\">Закрыть</button><br>";
$content .= "</td>";

$content .= "<td class=\"add-time-list-action-right\">";
$content .= "<button class=\"journal-action-button journal-action-button-wide\" onclick=\"add_addition_time();\">Добавить</button><br>";
$content .= "</td>";

$content .= "</tr>";
$content .= "</table><br>";  

include_once __DIR__ . "/../funcs.php";

$userID = (int)$_SESSION['ss_id'];
$currentDate = get_current_datetime_in_timezone_str( 1, 0 );
$user_dayTransitionTime = $_SESSION['ss_dayTransitionTime'];
$dateArr = datetimestr_to_day_start_stop_DT_ex_str( $currentDate, $user_dayTransitionTime );                                                                                                                                          
$startDTStr = $dateArr[0];
$stopDTStr = $dateArr[1];    

$addTimeInfo = get_add_work_info_by_user_and_day_ex( $userID, $startDTStr, $stopDTStr, 0 );

$content .= "<table id=\"addTimesTable\" class=\"add-time-list-table\">";
$content .= "<tr class=\"add-time-list-head\">";
$content .= "<td class=\"add_time add-time-list-date-cell\"><h5 class=\"big\">Начало<br>(дата, время)</h5></td>";
$content .= "<td class=\"add_time add-time-list-date-cell\"><h5 class=\"big\">Окончание<br>(дата, время)</h5></td>";
$content .= "<td class=\"add_time add-time-list-duration-cell\"><h5 class=\"big\">Длительность</h5></td>";
$content .= "<td class=\"add_time add-time-list-reason-cell\"><h5 class=\"big\">Основание</h5></td>";
$content .= "<td class=\"add_time add-time-list-comment-cell\"><h5 class=\"big\">Комментарий</h5></td>";
$content .= "<td class=\"add_time add-time-list-status-cell\"><h5 class=\"big\">Статус</h5></td>";
$content .= "<td class=\"add_time add-time-list-delete-cell\"><h5 class=\"big\">Удалить</h5></td>";
$content .= "</tr>";

$_SESSION['add_times_block_height'] = 90;

$bkColor = "#ffffff";
$useBkColor = 0;

{
  for ( $idx = 0; $idx < count( $addTimeInfo ); $idx ++ )
  {
    $addInf = $addTimeInfo[$idx];

    $id = (int)$addInf[8];
    $startDT = $addInf[0];
    $stopDT = $addInf[1];

    $startDT = substr($startDT, 0, 16);
    $stopDT = substr($stopDT, 0, 16);

    $reasonStr = $addInf[11];
    $description = $addInf[3];
    $approved = $addInf[4];
    $SUID = $addInf[5];
    $pauseMode = $addInf[7];

    if ( $pauseMode == 1 ){ continue; }    
    if ( $approved == 99 OR $approved == 100 OR $approved == 101 ){ continue; }    
   
    $duration = (int)$addInf[6];
    $durationStr = $duration > 0 ? format_time_( $duration ) : "";

    $superUserName = get_name_by_userid( $SUID );

    $disabled = "";
    $titleDel = "удалить запись";

    if ( $approved == 0 )
    { 
      $content1 = journal_status_label("на рассмотрении", "big");
      $statusClass = "";
    }
    else if ( $approved == -1 )
    { 
      $approvedStr = "отклонено";
      $statusClass = "add-time-list-status-refused";
      $decisionTitle = html_escape("решение принял: $superUserName");
      $content1 = "<div class=\"add-time-list-status-content\">";
      $content1 .= journal_status_label($approvedStr, "big");
      $content1 .= "<img title=\"$decisionTitle\" src=\"img/superuserBad.png\" alt=\"\">";
      $content1 .= "</div>";
      $disabled = "disabled";
      $titleDel = "title=\"запись уже заквитирована. Удаление невозможно\"";
    }
    else if ( $approved == 1 )
    { 
      $approvedStr = "принято";
      $statusClass = "add-time-list-status-accepted";
      $decisionTitle = html_escape("решение принял: $superUserName");
      $content1 = "<div class=\"add-time-list-status-content\">";
      $content1 .= journal_status_label($approvedStr, "big");
      $content1 .= "<img title=\"$decisionTitle\" src=\"img/superuserGood.png\" alt=\"\">";
      $content1 .= "</div>";
      $disabled = "disabled";
      $titleDel = "title=\"запись уже заквитирована. Удаление невозможно\"";
    }

    $rowClass = $bkColor == "#ffffff" ? "add-time-list-row" : "add-time-list-row-alt";

    $content .= "<tr class=\"$rowClass\">";
    $content .= "<td class=\"add_time add-time-list-date-cell\"><h5 class=\"middle\">" . html_escape($startDT) . "</h5></td>";
    $content .= "<td class=\"add_time add-time-list-date-cell\"><h5 class=\"middle\">" . html_escape($stopDT) . "</h5></td>";
    $content .= "<td class=\"add_time add-time-list-duration-cell\"><h5 class=\"middle\">" . html_escape($durationStr) . "</h5></td>";
    $content .= "<td class=\"add_time add-time-list-reason-cell\"><h5 class=\"middle\">" . html_escape($reasonStr) . "</h5></td>";
    $content .= "<td class=\"add_time add-time-list-comment-cell\"><h5 class=\"middle\">" . html_escape($description) . "</h5></td>";
    $content .= "<td class=\"add_time add-time-list-status-cell $statusClass\">";
    $content .= $content1;
    $content .= "</td>";
    $content .= "<td class=\"add_time add-time-list-delete-cell\">";
    $content .= "<button $titleDel $disabled class=\"journal-action-button journal-action-button-small-delete\" onclick=\"part_time_del($id);\">Удалить</button><br>";
    $content .= "</td>";
    $content .= "</tr>";
    if ( $useBkColor == 0 )
    {
      $useBkColor = 1;
      $bkColor = "";
    }
    else
    {
      $useBkColor = 0;
      $bkColor = "#ffffff";
    }                      
  }
}
$content .= "</table><br>";

echo $content;

?>
