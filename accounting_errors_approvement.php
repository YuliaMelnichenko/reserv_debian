<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
require_page_superuser();
include_once __DIR__ . '/funcs.php';
include __DIR__ . '/php_tori/connect.php';
save_last_location('accounting_errors_approvement.php');
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
$userID_ = (int)$_SESSION['ss_id'];
echo "<div align=\"left\">";

echo "<table border=0>";
  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width=250>";
      include_once __DIR__ . "/navigate.php";
    echo "</td>";

    $wholeWidth = 835;

    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width=$wholeWidth>";

      echo "<div id=\"accountingErrorsApprovementHeader\">";
        echo "<h5 class=\"dark\"><br>/уведомления по ошибкам учета<br><br></h5>";
      echo "</div>";

      $depthDays = get_accounting_errors_default_depth_days();

      echo "<div id=\"accountingErrorsApprovementTableScroll\">";
        echo "<table class=\"add_time\" id=\"accounting_errors_approvement_table_users\" border=1>";
          echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=250><h5 class=\"big\">Сотрудник</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=60><h5 class=\"big\">Всего</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=80><h5 class=\"big\">Принятые</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=105><h5 class=\"big\">Отклоненные</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=90><h5 class=\"big\">Удаленные</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=60><h5 class=\"big\">Новые</h5></td>";
            echo "<td class=\"add_time\" valign=\"middle\" align=\"center\" width=105><h5 class=\"big\">Просмотреть</h5></td>";
          echo "</tr>";

          $color = "#ddffff";
          $img = "go1.png";
          $rowCount = 0;

          $supervisedUserIDs = get_accounting_errors_supervised_user_ids($link, $userID_);

          if ($supervisedUserIDs === false) {
            echo "<tr><td colspan=7><h5 class=\"middle\">Не удалось загрузить список сотрудников.</h5></td></tr>";
          }
          else {
            foreach ($supervisedUserIDs as $userID) {

              sync_accounting_errors_for_user($link, $userID, $depthDays);

              $notificationCount = 0;
              $acceptedNotificationCount = 0;
              $refusedNotificationCount = 0;
              $deletedNotificationCount = 0;
              $newNotificationCount = 0;

              get_accounting_errors_counts_by_user(
                $link,
                $userID,
                $notificationCount,
                $acceptedNotificationCount,
                $refusedNotificationCount,
                $deletedNotificationCount,
                $newNotificationCount
              );

              $rowCount++;

              $userName = html_escape(get_user_name_by_id($userID));
              $muid = getMaskedUID(32, $userID);
              $userUrl = "accounting_errors_approvement_user.php?mid=$muid";
              $uhref = "location.href='$userUrl'";

              $cellStype = "middle";

              if ($newNotificationCount > 0) {
                $cellStype = "middleBlue1";
              }

              echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
                echo "<td class=\"add_time\" width=250 valign=\"middle\" align=\"left\"><h5 class=\"middle\">$userName</h5></td>";
                echo "<td class=\"add_time\" width=60 valign=\"middle\" align=\"center\"><h5 class=\"middle\">$notificationCount</h5></td>";
                echo "<td class=\"add_time\" width=80 valign=\"middle\" align=\"center\"><h5 class=\"middle\">$acceptedNotificationCount</h5></td>";
                echo "<td class=\"add_time\" width=105 valign=\"middle\" align=\"center\"><h5 class=\"middle\">$refusedNotificationCount</h5></td>";
                echo "<td class=\"add_time\" width=90 valign=\"middle\" align=\"center\"><h5 class=\"middle\">$deletedNotificationCount</h5></td>";
                echo "<td class=\"add_time\" width=60 valign=\"middle\" align=\"center\"><h5 class=\"$cellStype\">$newNotificationCount</h5></td>";
                echo "<td class=\"add_time\" width=105 valign=\"middle\" align=\"center\">";
                  echo "<button id=\"accountingErrorsViewBtn_$userID\" title=\"Просмотреть\" style=\"padding: 0px 0px 0px 0px; background-color:#ffffff; border:0px solid #888888; cursor:pointer;\" onclick=\"$uhref;\"><img src=\"img/$img\"></button>";
                echo "</td>";
              echo "</tr>";

              if ($color == "#ddffff") {
                $color = "#ffffff";
                $img = "go2.png";
              }
              else {
                $color = "#ddffff";
                $img = "go1.png";
              }
            }

            if ($rowCount == 0) {
              echo "<tr bgcolor=\"#ffffff\">";
                echo "<td class=\"add_time\" colspan=7 valign=\"middle\" align=\"center\"><h5 class=\"middle\">Ошибок учета у подчиненных нет</h5></td>";
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

<script type="text/javascript" src="js/tory.js"></script>
<script type="text/javascript" charset="utf-8">

function update_clock(){
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat){
    if (document.getElementById('dateTimeFieldNav')){
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
