<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . "/../inc/add_time_journal.php";

$userID = (int)$_SESSION['ss_id'];
$journal = get_add_time_journal_context(
  $link,
  $userID,
  get_current_datetime_in_timezone_str(1, 0),
  false
);

if ($journal === false) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

if ($journal === null) {
  deny_ajax_access(404, 'USER_NOT_FOUND');
}

$addTimeInfo = $journal['entries'];
$content = "<h5 class=\"big\">Работа вне офиса. Внесение сведений</h5><br>";

$content .= "<br><table border=0 width=1080>";
$content .= "<tr>";

$content .= "<td bordercolor=\"#000000\" width=\"500px\" valign=\"middle\" align=\"left\">";
$content .= "<button class=\"journal-action-button journal-action-button-wide journal-action-button-close\" onclick=\"cancel_time_add(); location.reload();\">Закрыть</button><br>";
$content .= "</td>";

$content .= "<td bordercolor=\"#000000\" width=\"520px\" valign=\"middle\" align=\"right\">";
$content .= "<button class=\"journal-action-button journal-action-button-wide\" onclick=\"add_addition_time();\">Добавить</button><br>";
$content .= "</td>";

$content .= "</tr>";
$content .= "</table><br>";

$content .= "<table id=\"addTimesTable\" border=1 bordercolor=\"#888888\">";
$content .= "<tr bgcolor=\"#DDDDDD\">";
$content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"122px\">"."<h5 class=\"big\">Начало<br>(дата, время)"."</h5></td>";
$content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"122px\">"."<h5 class=\"big\">Окончание<br>(дата, время)"."</h5></td>";
$content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"122px\">"."<h5 class=\"big\">Длительность"."</h5></td>";
$content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"182px\">"."<h5 class=\"big\">Основание"."</h5></td>";
$content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"250px\">"."<h5 class=\"big\">Комментарий"."</h5></td>";
$content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"130px\">"."<h5 class=\"big\">Статус"."</h5></td>";
$content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"92px\">"."<h5 class=\"big\">Удалить"."</h5></td>";
$content .= "</tr>";

$_SESSION['add_times_block_height'] = 90;

$bkColor = "#ffffff";
$useBkColor = 0;

{
  for ( $idx = 0; $idx < count( $addTimeInfo ); $idx ++ )
  {
    $addInf = $addTimeInfo[$idx];

    $id = $addInf['id'];
    $startDT = $addInf['start_datetime'];
    $stopDT = $addInf['stop_datetime'];

    $startDT = substr($startDT, 0, 16);
    $stopDT = substr($stopDT, 0, 16);

    $reasonStr = $addInf['reason_description'];
    $description = $addInf['employee_comment'];
    $approved = $addInf['status'];
    $duration = $addInf['duration'];
    $durationStr = $duration > 0 ? format_time_( $duration ) : "";

    $superUserName = $addInf['supervisor_name'];

    $disabled = "";
    $titleDel = "удалить запись";

    if ( $approved == 0 )
    {
      $content1 = journal_status_label("на рассмотрении", "big");
      $cellColor = $bkColor;
    }
    else if ( $approved == -1 )
    {
      $approvedStr = "отклонено"; $cellColor = "#FFAAAA";
      $decisionTitle = html_escape("решение принял: $superUserName");
      $ta_approved_str_add1 = " <img title=\"$decisionTitle\" src=\"img/superuserBad.png\">";

      $content1 = "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
        $content1 .= "<tr>";
          $content1 .= "<td width=\"80%\" align=\"left\" >";
            $content1 .= journal_status_label($approvedStr, "big");
          $content1 .= "</td>";
          $content1 .= "<td width=\"20%\" align=\"right\" >";
            $content1 .= "<h5 class=\"big\">$ta_approved_str_add1</h5>";
          $content1 .= "</td>";
        $content1 .= "</tr>";
      $content1 .= "</table>";
      $disabled = "disabled";
      $titleDel = "title=\"запись уже заквитирована. Удаление невозможно\"";
    }
    else if ( $approved == 1 )
    {
      $approvedStr = "принято"; $cellColor = "#AAFFAA";
      $decisionTitle = html_escape("решение принял: $superUserName");
      $ta_approved_str_add1 = " <img title=\"$decisionTitle\" src=\"img/superuserGood.png\">";

      $content1 = "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
        $content1 .= "<tr>";
          $content1 .= "<td width=\"80%\" align=\"left\" >";
            $content1 .= journal_status_label($approvedStr, "big");
          $content1 .= "</td>";
          $content1 .= "<td width=\"20%\" align=\"right\" >";
            $content1 .= "<h5 class=\"big\">$ta_approved_str_add1</h5>";
          $content1 .= "</td>";
        $content1 .= "</tr>";
      $content1 .= "</table>";
      $disabled = "disabled";
      $titleDel = "title=\"запись уже заквитирована. Удаление невозможно\"";
    }

    $content .= "<tr bgcolor=\"$bkColor\">";
    $content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"122px\"><h5 class=\"middle\">" . html_escape($startDT) . "</h5></td>";
    $content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"122px\"><h5 class=\"middle\">" . html_escape($stopDT) . "</h5></td>";
    $content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"122px\"><h5 class=\"middle\">" . html_escape($durationStr) . "</h5></td>";
    $content .= "<td class=\"add_time\" valign=\"middle\" align=\"left\" width = \"182px\"><h5 class=\"middle\">" . html_escape($reasonStr) . "</h5></td>";
    $content .= "<td class=\"add_time\" valign=\"middle\" align=\"left\" width = \"250px\"><h5 class=\"middle\">" . html_escape($description) . "</h5></td>";
    $content .= "<td class=\"add_time\" bgcolor=\"$cellColor\" valign=\"middle\" align=\"center\" width = \"130px\">";
    $content .= $content1;
    $content .= "</td>";
    $content .= "<td class=\"add_time\" valign=\"middle\" align=\"center\" width = \"92px\">";
    $content .= "<button $titleDel $disabled class=\"journal-action-button journal-action-button-small-delete\" onclick=\"part_time_del( $id );\">Удалить</button><br>";
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
