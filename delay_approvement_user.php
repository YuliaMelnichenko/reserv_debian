<?php
ob_start();
session_start();
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
////////////////////////////////////////////////////////
include_once __DIR__ . "/funcs.php";
include_once __DIR__ . "/php_tori/connect.php";
save_last_location( "delay_approvement.php" );
auth();
////////////////////////////////////////////////////////

$mid = $_GET['mid'];

$resArr = extractUidFromMaskedUID( $mid );
$uidValid = $resArr[0];
$userID = $resArr[1];

if ( $uidValid == 0 ){
  header('Location: '.'time_approvement.php');
}

echo "<div align=\"left\">";

include_once __DIR__ . "/php_tori/connect.php";

mysqli_set_charset($link, "utf8");

echo "<input id=\"recIDTempVal\" type=\"hidden\" value=\"\">";
echo "<input id=\"acceptTempVal\" type=\"hidden\" value=\"\">";
echo "<input id=\"penIDTempVal\" type=\"hidden\" value=\"\">";
echo "<input id=\"penDateTempVal\" type=\"hidden\" value=\"\">";
echo "<input id=\"penUserIDTempVal\" type=\"hidden\" value=\"\">";

echo "<table border=0>";
  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";
      include_once __DIR__ . "/navigate.php";
    echo "</td>";    

    $wholeWidth = 1272;

    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

    echo "<div id=\"delayHeader\">";
      echo "<h5 class=\"dark\"><br>/уведомления по опозданиям<br><br></h5>";
    echo "</div>";

$user_defaultStartTime = "10:00:00";
$user_allowedDelay = 30;

get_user_defStartTime_and_allowedDelay( $userID, $user_defaultStartTime, $user_allowedDelay );
$userName = get_user_name_by_id($userID);

$delayTimes = Array();

$delayTimes = get_all_delay_info_by_user( $userID, $user_defaultStartTime, $user_allowedDelay );

      if ( count( $delayTimes ) == 0 ){
        echo "<table id=\"add_time_approvement_table\" border=0>";
          echo "<tr>";
            echo "<td valign=\"middle\" width=1000 align=\"left\">"."<h5 class=\"bigbig17\">$userName</h5>"."</td>";
            echo "<td width=262 valign=\"middle\" align=\"right\">";
              echo "<button title = \"Назад\" style=\"padding: 5px 5px 5px 5px; width:73px; height:25px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"location.href='delay_approvement.php';\"><h5>Назад</h5></button>";
            echo "</td>";
          echo "</tr>";
        echo "</table>";

        echo "<h5><br>Нет сведений!</h5>";
        echo "</td>";
        echo "<tr>";
        echo "<table>";
        exit;
      }

$rWidth = $wholeWidth - 312;

echo "<table id=\"delay_approvement_table\" border=0>";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table border=0>";
        echo "<tr>";
          echo "<td valign=\"middle\" width=1000 align=\"left\">"."<h5 class=\"bigbig17\">$userName</h5>"."</td>";
          echo "<td width=262 valign=\"middle\" align=\"right\">";
            echo "<button title = \"Назад\" style=\"padding: 5px 5px 5px 5px; width:73px; height:25px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"location.href='delay_approvement.php';\"><h5>Назад</h5></button>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    echo "</td>";
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"nopadding\" width=1300 valign=\"middle\" align=\"left\">";

      echo "<table border=1>";
      echo "<tr bgcolor=\"#EEEEEE\" bordercolor=\"#888888\">";

      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Дата</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Время<br>прихода</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Длительность<br>опоздания</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Комментарий<br>работника</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>С кем предварительно<br>согласовано</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Лицо, принявшее<br> решения</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Комментарий лица,<br>принявшего решение</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Статус</h5>"."</td>";
      echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5>Управление</h5>"."</td>";
      echo "</tr>";
  
      $colorMode = 1;
      $color1 = "#ddffff";
      $color2 = "#ddeedd";
      $color3 = "#ffffff";

      foreach( $delayTimes as $delayTime ){
        $retDelay_id = $delayTime[0];
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

        $bgcolor = "";
        $accBtnDisabled = "";
        $refBtnDisabled = "";

        if ( $retDelay_agreed == 0 ){
          if ( $superUserName == "" ){
            $superUserName = "Ни с кем!";
          }
          $bgcolor1 = "#FFAAAA";
        }
        else if ( $retDelay_agreed == 1 ){
          $bgcolor1 = "";
        }   

        $accBtnImg = "accept_small.bmp";
        $refBtnImg = "refuse_small.bmp";

        if ( $retDelay_approved == 0 ){
          $content1 = "<h5 class=\"middleBold_r\">на рассмотрении</h5>";
          $bgcolor = '#ffffff'; 
          $delRestore = "1";  
        }
        else if ( $retDelay_approved == 1 ){
          $content1 = "<h5 class=\"middleBold_r\">принято</h5>";
          $bgcolor = "#AAFFAA";
          $accBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $delRestore = "1";
        }
        else if ( $retDelay_approved == -1 ){ 
          $content1 = "<h5 class=\"middleBold_r\">отклонено</h5>";
          $bgcolor = "#FFAAAA";
          $refBtnDisabled = "disabled";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "1";
        }
        else if ( $retDelay_approved == 99 OR $retDelay_approved == 100 OR $retDelay_approved == 101 ){
          $content1 = "<h5 class=\"big\">отклонено</h5>";
          $bgcolor = "#DDDDDD";
          $accBtnDisabled = "disabled";
          $refBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "0";
        }

        $time_duration = format_time_d_hhmmss_pure( $retDelay_duration );
  	
        if ( $colorMode == 0 ){
          $color = $color1;
          $colorMode = 1;
        }
        else{
          $color = $color3;
          $colorMode = 0;
        }

        echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
          echo "<td width=60 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($retDelay_start_date) . "</h5></td>";
          echo "<td width=100 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($retDelay_start_time) . "</h5></td>";
          echo "<td width=85 class=\"add_time\" valign=\"middle\" align=\"center\">"."<h5 class=\"small\">$time_duration</h5>"."</td>";
          echo "<td width=160 class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($retDelay_description) . "</h5></td>";
          echo "<td width=200 bgcolor=\"$bgcolor1\" class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
          echo "<td width=200 class=\"add_time\" valign=\"middle\" align=\"center\"><h5 class=\"small\">" . html_escape($acceptorName) . "</h5></td>";
          echo "<td width=160 class=\"add_time\" valign=\"middle\" align=\"left\"><h5 class=\"small\">" . html_escape($retDelay_acceptor_description) . "</h5></td>";
          echo "<td width=130 bgcolor=\"$bgcolor\" class=\"add_time\" valign=\"middle\" align=\"center\">$content1</td>";

          echo "<td class=\"add_time\" valign=\"middle\" align=\"center\">";
   
            echo "<table border=0>";
              echo "<tr>";
                echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" border=0>";
                  echo "<button onclick=\"accept_delay_for_user(" . (int) $retDelay_id . ", " . html_escape(js_encode($retDelay_acceptor_description)) . ", " . (int) $retDelay_penalty_id . ", " . html_escape(js_encode($retDelay_start_date)) . ", " . (int) $userID . ");\" $accBtnDisabled style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                    echo "<img title=\"Принять\" src=\"img/$accBtnImg\">";
                  echo "</button>";
                echo "</td>";
                echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" border=0>";
                  echo "<button onclick=\"refuse_delay_for_user(" . (int) $retDelay_id . ", " . html_escape(js_encode($retDelay_acceptor_description)) . ", " . (int) $retDelay_penalty_id . ", " . html_escape(js_encode($retDelay_start_date)) . ", " . (int) $userID . ");\" $refBtnDisabled style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                    echo "<img title=\"Отклонить\" src=\"img/$refBtnImg\">";
                  echo "</button>";
                // echo "</td>";
                //   echo "<td width=\"2\">";
                //   echo "</td>";
                echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" border=0>";
                  if ( $delRestore == 1 ){ 
                    echo "<button onclick=\"mark_as_deleted_delay_for_user(" . (int) $retDelay_id . "); location.reload();\" style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                      echo "<img title=\"Удалить\" src=\"img/delete_small.bmp\">";
                    echo "</button>";
                  }
                  else{
                    echo "<button onclick=\"mark_as_undeleted_delay_for_user(" . (int) $retDelay_id . "); location.reload();\" style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                      echo "<img title=\"Восстановить\" src=\"img/restore_small.bmp\">";
                    echo "</button>";
                  }
                echo "</td>";
              echo "</tr>";
            echo "</table>";   

          echo "</td>";  
      }
      echo "</table>";
    echo "</td>";
  echo "</tr>";
echo "</table>";
      
      echo "<div id=\"delay_approvement_desc\">";
        echo "<div class=\"comment\">";
          echo "<h5 class=\"bigbig\">Комментарий</h5>";
        echo "</div>";
        echo "<div class=\"text_box\">";
          echo "<textarea id=\"delay_part_desc_2\" style=\"width:250px; resize: none;\" cols=\"43\" rows=\"3\"></textarea>";
        echo "</div>";
        echo "<div class=\"box_btn\">";
          echo "<div>";
            echo "<button style=\"font-size: 100%; width:119px; height:20px; background-color:#ff8888; border:1px solid #888888;\" onclick=\"document.getElementById('delay_approvement_desc').style.display='none'; location.reload();\">Отмена</button>";
          echo "</div>";
          echo "<div>";
            echo "<button style=\"font-size: 100%; width:119px; height:20px; background-color:#88ff88; border:1px solid #888888;\" onclick=\"accept_refuse_delay_for_user_final( document.getElementById('recIDTempVal').value, document.getElementById('delay_part_desc_2').value, document.getElementById('acceptTempVal').value, document.getElementById('penIDTempVal').value, document.getElementById('penDateTempVal').value, document.getElementById('penUserIDTempVal').value ); location.reload();\">Сохранить</button>";
          echo "</div>";
        echo "</div>";
      echo "</div>";

    echo "</td>"; 
  echo "</tr>";
echo "</table>";
echo "</div>";
?>

<script type="text/javascript" src="js/tory.js"></script>

<?php
echo "</body>";
echo "</html>";  
?>
