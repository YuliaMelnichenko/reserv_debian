<?php
date_default_timezone_set("Asia/Novosibirsk");
session_start();
ob_start();

include_once "/var/www/tori/start.php";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>                                                                                                                   
<head>
<title>Отчет посещаемости</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META NAME="Author" CONTENT="InTec">
<link rel="stylesheet" type="text/css" href="style/style.css" />
<link rel="stylesheet" type="text/css" href="style/main.css" />
</head>
<body onload="show_selectors()" bgcolor="#ffffff">

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" src="js/tory.js"></script> 
<script type="text/javascript" charset="utf-8"> 

/*var timerIdSessValid=setInterval( "check_sess()", 10000 );

function check_sess()
{
  $.post('ajax/check_session_valid.php', RetSWT);                           
  function RetSWT(dat) 
  {
    if ( dat == 0 )
    {
      window.location=self.location;
    }
  } 
}     */

function set_period()
{	
  var report_type = document.getElementById('report_type').value;
  var start_report_date = document.getElementById('report_start_date').value;
  var stop_report_date = document.getElementById('report_stop_date').value;

//alert( report_type );

  if ( report_type == 7 )
  {
    document.getElementById('manual_rep').style.display='block';
  }
  else
  {
    document.getElementById('manual_rep').style.display='none';
    $.post('ajax/set_report_date_interval.php', {report_type: report_type, start_report_date: start_report_date, stop_report_date: stop_report_date}, RetSWT);                           
    function RetSWT(dat) 
    {
      window.location=self.location;
      if ( report_type == 7 )
      {
        document.getElementById('manual_rep').style.display='block';
      }
    }
  }
}

function manual_report_set()
{	
  var report_type = document.getElementById('report_type').value;
  var start_report_date = document.getElementById('report_start_date').value;
  var stop_report_date = document.getElementById('report_stop_date').value;

  $.post('ajax/set_report_date_interval.php', {report_type: report_type, start_report_date: start_report_date, stop_report_date: stop_report_date}, RetSWT);                           
  function RetSWT(dat) 
  { 
    window.location=self.location;
  }
}

function ta_delete( delID )
{	
  var perform=confirm('Запись будет удалена. Продолжить?')
  if ( perform == true )
  {
    $.post('ajax/time_delete.php', {delID: delID}, RetSWT);                           
    function RetSWT(dat) 
    {  
      window.location=self.location;
    }
  }
}

function show_adds_info( startDate, stopDate, userID )
{
  document.getElementById('adds_list_header').style.display='none';
  $.post('ajax/get_add_times_info.php', {startDate: startDate, stopDate: stopDate, userID: userID}, RetSWT);                           
  function RetSWT(dat) 
  {
     document.getElementById('adds_list_header').innerHTML = dat; 
     document.getElementById('adds_list_header').style.display='block';
  }
}

function show_pauses_info( startDate, stopDate, userID )
{
  document.getElementById('pauses_list_header').style.display='none';
  $.post('ajax/get_pauses_times_info.php', {startDate: startDate, stopDate: stopDate, userID: userID}, RetSWT);                           
  function RetSWT(dat) 
  {
     document.getElementById('pauses_list_header').innerHTML = dat; 
     document.getElementById('pauses_list_header').style.display='block';
  }
}

function close_add_time_list()
{
  document.getElementById('adds_list_header').style.display='none';
}

function close_pause_time_list()
{
  document.getElementById('pauses_list_header').style.display='none';
}

function show_penalties_info( startDate, stopDate, userID )
{
  document.getElementById('penalty_list_header').style.display='none';
  $.post('ajax/get_penalties_info.php', {startDate: startDate, stopDate: stopDate, userID: userID}, RetSWT);                           
  function RetSWT(dat) 
  {
     document.getElementById('penalty_list_header').innerHTML = dat; 
     document.getElementById('penalty_list_header').style.display='block';
  }
}

function close_penalties_list()
{
  document.getElementById('penalty_list_header').style.display='none';
}

///////////////////////

function show_selectors()
{
  var report_type = document.getElementById('report_type').value;

    console.log(report_type);

  if ( report_type == 7 )
  {
    document.getElementById('manual_rep').style.display='block';
  } 
  else
  {
    document.getElementById('manual_rep').style.display='none';
  } 
}

$.post('ajax/get_report_body.php', RetSWT4 );
function RetSWT4(dat4)
{
  if ( document.getElementById('report_body') )
  { 
    //document.getElementById('report_body_data').innerHTML = dat4; 
    document.getElementById('report_body').innerHTML = dat4; 
    
    if ( document.getElementById('time_report_table_head') && document.getElementById('time_report_table_body') )
    { 
      tableHeightHead = document.getElementById('time_report_table_head').offsetHeight;
      tableHeightBody = document.getElementById('time_report_table_body').offsetHeight;

      tableWidthBody = document.getElementById('time_report_table_body').offsetWidth + 40;

      if ( tableWidthBody < 340 )
      {
        tableWidthBody = 340;
      } 
      
//      alert(tableWidthBody); 

      tableAllHeight = tableHeightHead + tableHeightBody;

      win_h = $(window).height() - 170;   

      if ( tableAllHeight > win_h ){ tableAllHeight = win_h; }
      else{ tableAllHeight = tableAllHeight - 35; }

     // document.getElementById('report_body_data').style.height = tableAllHeight + "px";
      document.getElementById('report_body').style.height = tableAllHeight + "px";
      document.getElementById('report_body').style.width = tableWidthBody + "px";
      document.getElementById('report_head').style.width = tableWidthBody + "px";
    }
  }
} 

</script>
<?php
// session_start();

////////////////////////////////////////////////////////
include_once "/var/www/tori/funcs.php";
include_once "/var/www/tori/funcs_rep.php";

save_last_location( "my_report.php" );

//if ( !isset( $_SESSION['full_report'] ) )
//{
  auth();
//}
////////////////////////////////////////////////////////
$dateArr = get_current_datetime_in_timezone();
$currDate = $dateArr[2];
////////////////////////////////////////////////////////


echo "<div id=\"adds_list_header\">"; 
echo "</div>"; 

echo "<div id=\"pauses_list_header\">"; 
echo "</div>"; 

echo "<div id=\"penalty_list_header\">"; 
echo "</div>"; 

echo "<div align=\"left\">";

  echo "<table>";
    echo "<tr>";

      $ss_id_tmp = $_SESSION['ss_id'];

      $directorView = 0;
      if ( $ss_id_tmp == 5000 )
      {
        $directorView = 1;
      }  

      if ( $directorView == 0 )
      {
      	echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";
      include_once  "/var/www/tori/navigate.php";
      }
      else
      {
        echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 0>";
      }

      echo "</td>";
                                    
      $wholeWidth = 100;//379;

      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

        echo "<h5 class=\"dark\"><br>/отчет посещаемости<br><br></h5>";
        
///***
        if ( !isset($_SESSION['rep_start_stop_date_set']) )
        {
          $month_day = GetMonthDayD( $currDate );
          $offset = (int)$month_day - 1;

          $_SESSION['rep_start_date'] = DayDecDN( $currDate, $offset );
          $_SESSION['rep_stop_date'] = $currDate;
          $_SESSION['rep_start_stop_date_set'] = 1;
        }

        $selected = 0;

$rep_start_stop_date_mode = $_SESSION['rep_start_stop_date_mode'];

        if ( isset($_SESSION['rep_start_stop_date_mode']) )
        {
          $selected = $_SESSION['rep_start_stop_date_mode'] - 1;
        }


        $selectedArr = array();

        $selectedArr[0] = "";
        $selectedArr[1] = "";
        $selectedArr[2] = "";
        $selectedArr[3] = "";
        $selectedArr[4] = "";
        $selectedArr[5] = "";
        $selectedArr[6] = "";

        $selectedArr[$selected] = "selected";

        echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
          echo "<tr>";
            echo "<td class=\"nopadding\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
              echo "<h4 class=\"small\">Отчетный период: </h5>";
            echo "</td>";
            echo "<td valign=\"top\" align=\"left\" width = 140>";
              echo "<select onchange=\"set_period();\" class=\"flat\" id=\"report_type\" bgcolor=\"#888888\" width = 70 >";
                echo "<option value=\"1\" $selectedArr[0]>С начала недели</option>";
                echo "<option value=\"2\" $selectedArr[1]>С начала месяца</option>";
                echo "<option value=\"3\" $selectedArr[2]>С начала предыдущего месяца</option>";
                echo "<option value=\"4\" $selectedArr[3]>С начала квартала</option>";
//                echo "<option value=\"5\" $selectedArr[4]>Предыдущий квартал</option>";
//                echo "<option value=\"6\" $selectedArr[5]>С начала года</option>";
                echo "<option value=\"7\" $selectedArr[6]>Задать вручную</option>";
              echo "</select>"; 
            echo "</td>";
            echo "<td class=\"nopadding\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\" width = 320>";
              echo "<div id=\"manual_rep\" style=\"display:none;\">"; 
                if ( isset( $_SESSION['rep_start_date'] ) ){ $manRepStart = $_SESSION['rep_start_date']; } else { $manRepStart = $currDate; }
                if ( isset( $_SESSION['rep_stop_date'] ) ){ $manRepStop = $_SESSION['rep_stop_date']; } else { $manRepStop = $currDate; }

                echo "<input id=\"report_start_date\" align=\"center\" style=\"width:70px;\" type=\"text\" value=\"$manRepStart\">";
                echo " - <input id=\"report_stop_date\" align=\"center\" style=\"width:70px;\" type=\"text\" value=\"$manRepStop\">";
                echo "  <button class=\"$button_style\" style=\"font-size: 90%; width:100px; height:21px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"manual_report_set();\" name=\"nextBtn\">Показать</button>";
              echo "</div>";                                                                                                                                    
            echo "</td>";                                                                                                                                       
          echo "</tr>";
        echo "</table>";

        echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
          echo "<tr height = 10>";
            echo "<td>";
            echo "</td>";
          echo "</tr>";
          echo "<tr>";
            echo "<td class=\"nopadding\" bordercolor=\"#888888\" valign=\"middle\" align=\"left\">";
              echo "<h4 class=\"small\">Выбранный отчетный период: ".$_SESSION['rep_start_date']." - ".$_SESSION['rep_stop_date'];
            echo "</td>";
          echo "</tr>";
        echo "</table>";

        $svID = $_SESSION['ss_id'];

        $user_defaultStartTimeStr = $_SESSION['ss_defaultStartTime'];
        $user_allowedDelay = $_SESSION['ss_allowedDelay'];

        $rep_start_date = $_SESSION['rep_start_date'];
        $rep_stop_date = $_SESSION['rep_stop_date'];

//echo "{{{{{{{{{{{{{ $rep_stop_date}}}}}}}}}}}}}}}}<br>";

        $userIDs = array();

        if ( !isset( $_SESSION['full_report'] ) )
        {
          $usersInfo = get_group_user_info_by_svID_for_report_ex( $svID );
          //$userIDs =   get_group_user_IDs_by_svID_for_report( $svID );
        }
        else
        {
          //$usersInfo = get_group_user_info_by_svID( -1, 0 );
        }

        $userCnt = count($usersInfo[0]);

//echo "{{{{{{{{{{{{{ $userCnt}}}}}}}}}}}}}}}}<br>";

        for ( $userNum = 0; $userNum < $userCnt; $userNum ++ )
        {
          $userID = $usersInfo[0][$userNum];	
          $userRate = $usersInfo[2][$userNum];	

          $stats = get_stat_set_by_range_full_ex( $rep_start_date, $rep_stop_date, $userID, $userRate );

          $usersInfo[7][$userNum] = $stats;

          if ( $userID == 101 )
          {
              //$ff = $usersInfo[7][$userNum]
             //echo 
          }
        }    

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $rowsContents = get_report_body_row_contents( $usersInfo );

        $rowsDTContent = $rowsContents[0];
        $rowsContent = $rowsContents[1];

        ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

        $dateWidth = 80;
        $cellWidth = 105;
        $layersWidth = $dateWidth + $cellWidth*$userCnt + $userCnt*20;
        $layersWidth = 500;

        echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
          echo "<tr height = 10>";
            echo "<td>";
            echo "</td>";
          echo "</tr>";
        echo "</table>";


      echo "<div class=\"report_window_main1\" id=\"report_window_main1\">"; 

        echo "<table class = \"no_padding\">";
          echo "<tr>";

            echo "<td class=\"report_no_padding_no_border\">";    
              echo "<div class=\"report_window_head_left\" id=\"report_window_head_left\">"; 
                echo "<img src=\"/img/report_head_left.png\">";         
              echo "</div>";    
            echo "</td>";    
 
            echo "<td class=\"report_no_padding_no_border\">";    
              if ( $userCnt == 1 )
              {
                echo "<div class=\"report_window_head_single\" id=\"report_window_head_single\">";          
              }
              else
              {
                echo "<div class=\"report_window_head\" id=\"report_window_head\">";          
              }          
                echo "<table>";
                  //Заголовок
                  echo "<tr>";
                    for ( $userNum = 0; $userNum < $userCnt; $userNum ++ )
                    {
                      $userFIO = $usersInfo[1][$userNum];	
             
                      echo "<td class=\"report_no_padding\" bgcolor=\"#ffffff\" valign=\"middle\" align=\"center\">";
                        echo "<div class=\"report_head_name\">";  					                    
                          echo "<h5>".$userFIO."</h5>";
                        echo "</div>";
                      echo "</td>";  
                    }
//                    if ( $userCnt != 1 )
                    {
                      echo "<td class=\"report_no_padding\" bgcolor=\"#ffffff\" valign=\"middle\" align=\"center\">";
                        echo "<div class=\"report_head_stub\">";             
                        echo "</div>";
                      echo "</td>";
                    }       
                echo "</table>";
              echo "</div>";    
            echo "</td>";    

          echo "</tr>";




          echo "<tr>";

            echo "<td class=\"report_no_padding_no_border\">";    
              echo "<div class=\"report_window_left\" id=\"report_window_left\">";          
                echo "<table class = \"no_padding\">";

                  //Левая панель
                  for ( $idx = count( $rowsDTContent ); $idx >= 0; $idx -- )
                  {
                    echo "<tr>";
                      echo $rowsDTContent[$idx];  
                    echo "</tr>";
                  }     
                  echo "<tr>";
                    echo "<td class=\"report_no_padding_no_border\" valign=\"middle\" align=\"center\">";
                      echo "<div class=\"report_head_stub_left\">";             
                      echo "</div>";
                    echo "</td>";       
                  echo "</tr>";       

                echo "</table>";
              echo "</div>";    
            echo "</td>";    
 
            echo "<td class=\"report_no_padding_no_border\">";    
              if ( $userCnt == 1 )
              {
                echo "<div class=\"report_window_single\" id=\"report_window_single\" onscroll=\"make_div_scroll_single();\">";          
              }
              else
              {
                echo "<div class=\"report_window\" id=\"report_window\" onscroll=\"make_div_scroll();\">";
              }          
                echo "<table>";
                  //Тело
                  for ( $idx = count( $rowsContent ); $idx >= 0; $idx -- )
                  {
                    echo "<tr>";
                      echo $rowsContent[$idx];  
                    echo "</tr>";
                  }


                 /* echo "<tr>";
                  for ( $idx = count( $rowsContent ); $idx >= 0; $idx -- )
                  {
                    echo "<td class=\"report_no_padding\" bgcolor=\"#ffffff\" valign=\"middle\" align=\"center\">";
                      echo "<div class=\"report_head_stub\">";             
                      echo "</div>";
                    echo "</td>";
                  }       
                  echo "</tr>";  */ 

                echo "</table>"; 
              echo "</div>";
            echo "</td>";
    
          echo "</tr>";
        echo "</table>";

      echo "</div>"; 


      echo "</td>";
    echo "</tr>";
  echo "</table>";

  echo "<font size=\"2\" color=\"#444444\" face=\"Arial\">";
include_once "/var/www/tori/end.php";
  echo "</font>"; 

echo "</div>";
?>
<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" charset="utf-8"> 

function update_clock()
{
  $.post('ajax/get_current_day_time.php', RetSWT);                           
  function RetSWT(dat) 
  {
    if ( document.getElementById('dateTimeFieldNav') )
    {
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId=setInterval( "update_clock()", 10000 );      

</script>

</body>
</html> 
