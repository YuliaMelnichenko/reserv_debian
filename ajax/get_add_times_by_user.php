<?php
session_start();


require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$_SESSION['add_time_page_mode'] = 2;
$add_time_journal_deep = $_SESSION['add_time_journal_deep'];

$userID = $_POST['user'];

if ( $userID != -1 )
{ 
  $_SESSION['add_time_page_user_id'] = $userID;
}
else
{ 
  $userID = $_SESSION['add_time_page_user_id'];
}

$userName = get_user_name_by_id($userID);

echo "<table id=\"add_time_approvement_table\" border=0>";
  echo "<tr>";
    echo "<td class=\"nopadding_s\">";
      echo "<table border=0>";
        echo "<tr>";
          echo "<td valign=\"middle\" width=1014 align=\"left\">"."<h5 class=\"bigbig17\">$userName</h5>"."</td>";
          echo "<td width=10 valign=\"middle\" align=\"right\">";
            echo "<button title = \"Назад\" style=\"padding: 5px 5px 5px 5px; width:73px; height:25px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"add_time_go_back();\"><h5>Назад</h5></button>";
          echo "</td>";
        echo "</tr>";
      echo "</table>";
    echo "</td>";     
  echo "</tr>";
  echo "<tr>";
    echo "<td class=\"nopadding\" valign=\"middle\" align=\"left\">";

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

      $addTimeInfo = get_all_add_work_info_by_user( $userID );

      for ( $idx = 0; $idx < count( $addTimeInfo ); $idx ++ )
      {
        $addInf = $addTimeInfo[$idx];

        $ta_id = $addInf[8];
        $ta_start_dt = $addInf[0];
        $ta_stop_dt = $addInf[1];
        $ta_duration = $addTime[6];

        $ta_reason_description = $addInf[11];
        $ta_description = $addInf[3];
        $ta_SUdescription = $addInf[10];
        $ta_approved = $addInf[4];
        $ta_superuser = $addInf[5];
        
        $ta_approved_str = "На рассмотрении";

        $superUserName = get_superuser_name_by_id( $ta_superuser );

        if ( $ta_approved == 0 )
        { 
          $approvedStr = "<h5 class=\"middleBold_r\">на рассмотрении</h5>";
          $cellColor = $bkColor; 
        }
        else if ( $ta_approved == 1 )
        { 
          $approvedStr = "<h5 class=\"middleBold_r\">принято</h5>";
        }   
        else if ( $ta_approved == -1 )
        { 
          $approvedStr = "<h5 class=\"middleBold_r\">отклонено</h5>";
        }
        else if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 )
        { 
          $approvedStr = "<h5 class=\"middleBold_r\">удалено</h5>"; 
        }

        $time_duration = format_time_( strtotime($ta_stop_dt) - strtotime($ta_start_dt) );

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

        $buttonAdd1 = "";

        $bgcolor = "";
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
          $bgcolor = "#AAFFAA";
        }
        else if ( $ta_approved == -1 )
        {
          $refBtnDisabled = "disabled";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "1";
          $bgcolor = "#FFAAAA";
        }
        else if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 )
        {
          $accBtnDisabled = "disabled";
          $refBtnDisabled = "disabled";
          $accBtnImg = "acceptDis_small.bmp";
          $refBtnImg = "refuseDis_small.bmp";
          $delRestore = "0";
          $bgcolor = "#DDDDDD";
        }

        echo "<tr bgcolor=\"$color\" bordercolor=\"#888888\">";
        echo "<td class=\"add_time\" width=100 valign=\"middle\" align=\"center\"><h5 class=\"small\">".$ta_start_dt."</h5></td>";
        echo "<td class=\"add_time\" width=100 valign=\"middle\" align=\"center\"><h5 class=\"small\">".$ta_stop_dt."</h5></td>";
        echo "<td class=\"add_time\" width=85 valign=\"middle\" align=\"center\"><h5 class=\"small\">".$time_duration."</h5></td>";
        echo "<td class=\"add_time\" width=100 valign=\"middle\" align=\"left\"><h5 class=\"small\">".$ta_reason_description."</h5></td>";
        echo "<td class=\"add_time\" width=140 valign=\"middle\" align=\"left\"><h5 class=\"small\">".$ta_description."</h5></td>";
        echo "<td class=\"add_time\" width=140 valign=\"middle\" align=\"center\">"."<h5 class = \"small\">$superUserName</h5>"."</td>";
        echo "<td class=\"add_time\" width=140 valign=\"middle\" align=\"left\"><h5 class=\"small\">".$ta_SUdescription."</h5></td>";
        echo "<td class=\"add_time\" width=115 bgcolor=\"$bgcolor\" valign=\"middle\" align=\"center\">$approvedStr</td>";
        echo "<td class=\"add_time\" width=70 valign=\"middle\" align=\"center\">";

          echo "<table border=0>";
            echo "<tr>";
              echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" border=0>";
                echo "<button onclick=\"accept_add_time_for_user( '$ta_id', '$ta_SUdescription' );\" $accBtnDisabled style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                  echo "<img title=\"Принять\" src=\"img/$accBtnImg\">";                   
                echo "</button>";
              echo "</td>";
              echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\" border=0>";
                echo "<button onclick=\"refuse_add_time_for_user('$ta_id', '$ta_SUdescription' );\" $refBtnDisabled style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                  echo "<img title=\"Отклонить\" src=\"img/$refBtnImg\">";                   
                echo "</button>";
              echo "</td>";
              echo "<td width=\"2\">";
              echo "</td>";
              echo "<td class=\"nopadding_s\" valign=\"middle\" align=\"center\">";
                if ( $delRestore == 1 )
                { 
                  echo "<button onclick=\"mark_as_deleted_add_time_for_user( '$ta_id' );\" style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
                    echo "<img title=\"Удалить\" src=\"img/delete_small.bmp\">";                   
                  echo "</button>";
                }
                else
                {
                  echo "<button onclick=\"mark_as_undeleted_add_time_for_user( '$ta_id' );\" style=\"padding: 0px 0px 0px 0px; width:14px; height:14px; border:0px solid #888888;\">";
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