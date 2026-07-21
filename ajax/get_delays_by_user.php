<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
ajax_text_headers();

$_SESSION['delay_page_mode'] = 2;

$userID = request_post_int('user');

if ($userID === -1) {
  $userID = isset($_SESSION['delay_page_user_id'])
    ? (int)$_SESSION['delay_page_user_id']
    : 0;
}

if ($userID <= 0) {
  deny_ajax_access(400, 'INVALID_USER');
}

require_ajax_self_or_superuser($userID);
$_SESSION['delay_page_user_id'] = $userID;

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";
require_once __DIR__ . "/../inc/delay_journal.php";

$currentDate = get_current_datetime_in_timezone()[2];
$journal = get_delay_journal_context($link, $userID, $currentDate);

if ($journal === false) {
  ajax_database_error($link, __FILE__ . ':' . __LINE__);
  exit;
}

if ($journal === null) {
  deny_ajax_access(404, 'USER_NOT_FOUND');
}

$userName = $journal['user_name'];
$delayTimes = $journal['entries'];

echo "<table id=\"delay_approvement_table\" border=0>";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table border=0>";
        echo "<tr>";
          echo "<td valign=\"middle\" width=950 align=\"left\"><h5 class=\"bigbig17\">" . html_escape($userName) . "</h5></td>";
          echo "<td width=10 valign=\"middle\" align=\"right\">";
            echo "<button class=\"journal-back-button\" title=\"Назад\" onclick=\"delay_go_back();\"><h5>Назад</h5></button>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"nopadding\" width=1300 valign=\"middle\" align=\"left\">";

      echo "<table border=1>";
      echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";

      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5>Дата</h5></td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5>Время<br>прихода</h5></td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5>Длительность<br>опоздания</h5></td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5>Комментарий<br>работника</h5></td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5>С кем предварительно<br>согласовано</h5></td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5>Лицо, принявшее<br> решения</h5></td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5>Комментарий лица,<br>принявшего решение</h5></td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5>Статус</h5></td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\"><h5>Управление</h5></td>";
      echo "</tr>";

      $colorMode = 1;
      $color1 = "#ddffff";
      $color3 = "#ffffff";

      foreach ($delayTimes as $delayTime) {
        $bgcolor = "";
        $bgcolor1 = "";
        $accBtnDisabled = "";
        $refBtnDisabled = "";
        $superUserName = $delayTime['supervisor_name'];
        $acceptorName = $delayTime['acceptor_name'];

        if ($delayTime['agreed'] === 0) {
          if ($superUserName === "") {
            $superUserName = "Ни с кем!";
          }
          $bgcolor1 = "#FFAAAA";
        }

        $accBtnImg = "accept_small.bmp";
        $refBtnImg = "refuse_small.bmp";
        $delRestore = "1";
        $content1 = journal_status_label("на рассмотрении");

        if ($delayTime['status'] === 1) {
          $content1 = journal_status_label("принято");
          $bgcolor = "#AAFFAA";
          $accBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
        }
        else if ($delayTime['status'] === -1) {
          $content1 = journal_status_label("отклонено");
          $bgcolor = "#FFAAAA";
          $refBtnDisabled = "disabled";
          $refBtnImg = "refuseDis_small.bmp";
        }
        else if (in_array($delayTime['status'], array(99, 100, 101), true)) {
          $content1 = journal_status_label("отклонено", "big");
          $bgcolor = "#DDDDDD";
          $accBtnDisabled = "disabled";
          $refBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "0";
        }

        $timeDuration = format_time_d_hhmmss_pure($delayTime['duration']);

        if ($colorMode === 0) {
          $color = $color1;
          $colorMode = 1;
        }
        else {
          $color = $color3;
          $colorMode = 0;
        }

        echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
          echo "<td width=100 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($delayTime['date']) . "</h5></td>";
          echo "<td width=100 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($delayTime['arrival']) . "</h5></td>";
          echo "<td width=85 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($timeDuration) . "</h5></td>";
          echo "<td width=160 class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($delayTime['employee_comment']) . "</h5></td>";
          echo "<td width=140 bgcolor=\"$bgcolor1\" class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
          echo "<td width=140 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($acceptorName) . "</h5></td>";
          echo "<td width=160 class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($delayTime['decision_comment']) . "</h5></td>";
          echo "<td width=130 bgcolor=\"$bgcolor\" class=\"add_time\" valign=\"middle\" align=\"center\">$content1</td>";

          echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">";
            echo "<table border=0>";
              echo "<tr>";
                echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" border=0>";
                  echo "<button class=\"journal-icon-button\" onclick=\"accept_delay_for_user(" . $delayTime['id'] . ", " . html_escape(js_encode($delayTime['decision_comment'])) . ", " . $delayTime['penalty_id'] . ", " . html_escape(js_encode($delayTime['date'])) . ", " . $userID . ");\" $accBtnDisabled>";
                    echo "<img title=\"Принять\" src=\"img/$accBtnImg\">";
                  echo "</button>";
                echo "</td>";
                echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" border=0>";
                  echo "<button class=\"journal-icon-button\" onclick=\"refuse_delay_for_user(" . $delayTime['id'] . ", " . html_escape(js_encode($delayTime['decision_comment'])) . ", " . $delayTime['penalty_id'] . ", " . html_escape(js_encode($delayTime['date'])) . ", " . $userID . ");\" $refBtnDisabled>";
                    echo "<img title=\"Отклонить\" src=\"img/$refBtnImg\">";
                  echo "</button>";
                echo "</td>";
                echo "<td width=\"2\"></td>";
                echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" border=0>";
                  if ($delRestore === "1") {
                    echo "<button class=\"journal-icon-button\" onclick=\"mark_as_deleted_delay_for_user(" . $delayTime['id'] . ");\">";
                      echo "<img title=\"Удалить\" src=\"img/delete_small.bmp\">";
                    echo "</button>";
                  }
                  else {
                    echo "<button class=\"journal-icon-button\" onclick=\"mark_as_undeleted_delay_for_user(" . $delayTime['id'] . ");\">";
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
