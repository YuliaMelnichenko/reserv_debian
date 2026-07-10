<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
include_once __DIR__ . "/funcs.php";
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
echo "<link rel=\"stylesheet\" href=\"style/style.css\">";
echo "<link rel=\"stylesheet\" href=\"style/main.css\">";
echo "</head>";
echo "<body bgcolor=\"#ffffff\" >";
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

var timerId=setInterval( "update_clock()", 10000 );
</script>

<?php
echo "<div align=\"left\">";

include __DIR__ . "/php_tori/connect.php";

mysqli_set_charset($link, "utf8");

  echo "<table>";
    echo "<tr>";
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";
        include_once __DIR__ . "/navigate.php";
      echo "</td>";

      $wholeWidth = 1158;
      $wholeTableWidth = $wholeWidth;

      echo "<td id=\"add_time_content_width\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

        echo "<div id=\"addTimeHeader\">";
          echo "<h5 class=\"dark\"><br>/уведомления по работе вне офиса<br><br></h5>";
      echo "</div>";

      $userName = get_user_name_by_id($userID);
      $backUrl = "time_approvement.php";

      $addTimeInfo = get_all_add_work_info_by_user( $userID, 0 );

      if ( count( $addTimeInfo ) == 0 ){
        echo "<table id=\"add_time_approvement_table\" border=0>";
          echo "<tr>";
            echo "<td valign=\"middle\" width=1074 align=\"left\">"."<h5 class=\"bigbig17\">$userName</h5>"."</td>";
            echo "<td width=10 valign=\"middle\" align=\"right\">";
              echo "<button title = \"Назад\" style=\"padding: 5px 5px 5px 5px; width:73px; height:25px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"location.href='$backUrl';\"><h5>Назад</h5></button>";
            echo "</td>";
          echo "</tr>";
        echo "</table>";
        echo "<h5><br>Нет сведений!</h5>";
        echo "</td>";               
        echo "<tr>";
        echo "<table>";
        exit;
      }

echo "<table id=\"add_time_approvement_table\" border=0>";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table border=0>";
        echo "<tr>";
          echo "<td valign=\"middle\" width=1074 align=\"left\">"."<h5 class=\"bigbig17\">$userName</h5>"."</td>";
          echo "<td width=10 valign=\"middle\" align=\"right\">";
            echo "<button title = \"Назад\" style=\"padding: 5px 5px 5px 5px; width:73px; height:25px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"location.href='$backUrl';\"><h5>Назад</h5></button>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"nopadding\" valign=\"middle\" align=\"left\">";

      echo "<div class=\"notification-table-scroll notification-table-scroll-full\">";
      echo "<table border=1>";
      echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";

      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Начало<br>(дата, время)</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Окончание<br>(дата, время)</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Длительность</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Основание</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Комментарий<br>работника</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Лицо,<br>принявшее решение</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Комментарий лица,<br>принявшего решение</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Статус</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Управление</h5>"."</td>";
      echo "</tr>";
  
      $colorMode = 1;
      $color1 = "#ddffff";
      $color2 = "#ddeedd";
      $color3 = "#ffffff";

      for ( $idx = 0; $idx < count( $addTimeInfo ); $idx ++ ){
        $addInf = $addTimeInfo[$idx];

        $ta_id = $addInf[8];
        $ta_start_dt = $addInf[0];
        $ta_stop_dt = $addInf[1];
        $ta_duration = $addInf[6];

        $ta_reason_description = $addInf[11];
        $ta_description = $addInf[3];
        $ta_SUdescription = $addInf[10];
        $ta_approved = $addInf[4];
        $ta_superuser = $addInf[5];
        
        $ta_approved_str = "На рассмотрении";

        $superUserName = get_superuser_name_by_id( $ta_superuser );

        if ( $ta_approved == 0 ){
          $approvedStr = "<h5 class=\"middleBold_r\">на рассмотрении</h5>";
          $cellColor = '#ffffff';
        }
        else if ( $ta_approved == 1 ){
          $approvedStr = "<h5 class=\"middleBold_r\">принято</h5>";
        }
        else if ( $ta_approved == -1 ){ 
          $approvedStr = "<h5 class=\"middleBold_r\">отклонено</h5>";
        }
        else if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 ){
          $approvedStr = "<h5 class=\"middleBold_r\">удалено</h5>";
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

        $buttonAdd1 = "";

        $bgcolor = "";
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
          $bgcolor = "#AAFFAA";
        }
        else if ( $ta_approved == -1 ){
          $refBtnDisabled = "disabled";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "1";
          $bgcolor = "#FFAAAA";
        }
        else if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 ){
          $accBtnDisabled = "disabled";
          $refBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "0";
          $bgcolor = "#DDDDDD";
        }

        echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
        echo "<td class=\"add_time\" width=100 valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($ta_start_dt) . "</h5></td>";
        echo "<td class=\"add_time\" width=100 valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($ta_stop_dt) . "</h5></td>";
        echo "<td class=\"add_time\" width=85 valign=\"middle\" align=\"center\"><h5 class=\"small\">".$time_duration."</h5></td>";
        echo "<td class=\"add_time\" width=100 valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($ta_reason_description) . "</h5></td>";
        echo "<td class=\"add_time\" width=140 valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($ta_description) . "</h5></td>";
        echo "<td class=\"add_time\" width=200 valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
        echo "<td class=\"add_time\" width=140 valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($ta_SUdescription) . "</h5></td>";
        echo "<td class=\"add_time\" width=115 bgcolor=\"$bgcolor\" valign=\"middle\" align=\"center\">$approvedStr</td>";
        echo "<td class=\"add_time\" width=70 valign=\"middle\" align=\"center\">";

          echo "<table border=0>";
            echo "<tr>";
              echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" border=0>";
                echo "<button onclick=\"accept_add_time_for_user(" . (int) $ta_id . ", " . html_escape(js_encode($ta_SUdescription)) . ");\" $accBtnDisabled style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                  echo "<img title=\"Принять\" src=\"img/$accBtnImg\">";
                echo "</button>";
              echo "</td>";
              echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\" border=0>";
                echo "<button onclick=\"refuse_add_time_for_user(" . (int) $ta_id . ", " . html_escape(js_encode($ta_SUdescription)) . ");\" $refBtnDisabled style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                  echo "<img title=\"Отклонить\" src=\"img/$refBtnImg\">";
                echo "</button>";
              echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">";
                if ( $delRestore == 1 ){
                  echo "<button onclick=\"mark_as_deleted_add_time_for_user(" . (int) $ta_id . "); location.reload();\" style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                    echo "<img title=\"Удалить\" src=\"img/delete_small.bmp\">";
                  echo "</button>";
                }
                else{
                  echo "<button onclick=\"mark_as_undeleted_add_time_for_user(" . (int) $ta_id . "); location.reload();\" style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
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
        echo "<textarea id=\"add_time_part_desc_2\" style=\"width:250px; resize: none;\" cols=\"43\" rows=\"3\"></textarea>";
      echo "</div>";
      echo "<div class=\"box_btn\">";
        echo "<div>";
        echo "<button style=\"font-size: 100%; width:119px; height:20px; background-color:#ff8888; border:1px solid #888888;\" onclick=\"document.getElementById('add_time_approvement_desc').style.display='none'; location.reload();\">Отмена</button>";
        echo "</div>";
        echo "<div>";
        echo "<button style=\"font-size: 100%; width:119px; height:20px; background-color:#88ff88; border:1px solid #888888;\" onclick=\"accept_refuse_add_time_for_user_final( document.getElementById('recIDTempVal').value, document.getElementById('add_time_part_desc_2').value, document.getElementById('acceptTempVal').value ); location.reload();\">Сохранить</button>";
        echo "</div>";
      echo "</div>";
    echo "</div>";
?>

<script type="text/javascript" src="js/tory.js"></script>

<?php
echo "</body>";
echo "</html>";
?>
