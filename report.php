<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
require_page_auth();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>                                                                                                                   
<head>
<title>Отчет посещаемости</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META NAME="Author" CONTENT="InTec">
<link rel="stylesheet" type="text/css" href="style/main.css" />
</head>
<body bgcolor="#ffffff">
<div align="left">

<?php
$report_start_date = "2013-01-01";
$report_stop_date = "2013-08-01";

$read_mode = access_current_user_is_director() ? 1 : 2;

include_once __DIR__ . "/funcs.php";

$err = array();
$dates = array();
$ids = array();
$surn = array();
$fname = array();
$lname = array();
$week_dur = array();
$month_dur = array();
$work_day = array();
$day_off = array();

$cont_key = 0; 
	
include_once __DIR__ . "/php_tori/connect.php";

  
  $query = db_query($link, "SELECT * FROM work_dayoff order by date asc");
  $merr=mysqli_error($link);
  if ( !$query ) {
    echo database_error_message($link, __FILE__ . ':' . __LINE__);
  }
  else{
    $vn=mysqli_num_rows($query);
    if ( $vn > 0 ){
      $cont_key = 1;
      while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)){
	if ( $row["type"] == 1 ){
	  $work_day[] = $row["date"];
  }
	if ( $row["type"] == 0 ){
	  $day_off[] = $row["date"];
  }
      } 
    }    
  } 
  
  $cur_date = strtotime( $report_start_date );
  $report_stop_date_time = DayInc( strtotime( $report_stop_date ) );

  while( 1 )
  {

    $dates[] = date( "Y-m-d", $cur_date );

    $cur_date = DayInc( $cur_date );

    if ( $cur_date == $report_stop_date_time )
      break;
  }  
 
  if ( $read_mode == 1 )
  {
    mysqli_set_charset($link, "utf8");

    $query2 = db_query($link, "SELECT * FROM employees");
    $merr = mysqli_error($link);

    if ( !$query2 ) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
    }
    else{
      $vn2=mysqli_num_rows($query2);
      if ( $vn2 > 0 ){
        $cont_key = 1;
        while($row2 = mysqli_fetch_array($query2, MYSQLI_ASSOC)){
	        $ids[] = $row2["ID"];
	        $surn[] = $row2["SURNAME"];
          $fname[] = $row2["FIRSTNAME"];
	        $lname[] = $row2["LASTNAME"];
        } 
      }    
    }
  }
  if ( $read_mode == 2 ){
    $temp_id = (int)$_SESSION['ss_id'];

    mysqli_set_charset($link, "utf8");

    $query2 = db_query($link, "SELECT * FROM employees where ID = ?", 'i', array($temp_id));
    $merr=mysqli_error($link);
    if ( !$query2 ) {
      echo database_error_message($link, __FILE__ . ':' . __LINE__);
    }
    else{
      $vn2=mysqli_num_rows($query2);
      if ( $vn2 > 0 ){
        $cont_key = 1;
        while($row2 = mysqli_fetch_array($query2, MYSQLI_ASSOC)){
	        $ids[] = $row2["ID"];
	        $surn[] = $row2["SURNAME"];
          $fname[] = $row2["FIRSTNAME"];
	        $lname[] = $row2["LASTNAME"];
        } 
      }    
    }
  }

  if ( $cont_key == 0)
    die();

  $color_code = 0;
 
  echo "<table border=\"1\">";

  echo "<tr align = \"center\">";
  echo "<td><h5 class=\"fio\">Дата</h5></td>";
  
  for ( $i = 0; $i < count($ids); $i++ ){
    echo "<td><h5 class=\"fio\">" . html_escape($surn[$i] . " " . $fname[$i] . " " . $lname[$i]) . "</h5></td>";
  }
  echo "</tr>";

  $now_date_time = strtotime( date('Y-m-d') );

  echo "<h5 class=\"total_week_month\">Отчет за период с ".$report_start_date." по ".$report_stop_date."</h5><br>";

  echo "<a href=\"index.php\" class=\"ml\">на главную</a><br><br>";

  $day_norm = 8;

  $week_day_cnt = -1;
  $month_day_cnt = -1;
  
  foreach ($dates as $date_one){

    $week_day = GetWeekDay( $date_one );
    $month_day = GetMonthDay( $date_one );


    if ( $week_day == 6 OR $week_day == 0 )
      $is_day_off = 1;
    else
      $is_day_off = 0;

    for ( $j=0; $j< count( $work_day ); $j++ ){
      if ( $date_one == $work_day[$j] ){
         $is_day_off = 0;
         break;
      }
    }
        
    for ( $j=0; $j< count( $day_off ); $j++ ){
      if ( $date_one == $day_off[$j] ){
        $is_day_off = 1;
        break;
      }
    }

    if ( $is_day_off != 1 ){
      $week_day_cnt++;
      $month_day_cnt++;
    }
 

    if ( $week_day == 1 ){
      echo "<tr>";
      echo "<td class=\"report-summary-cell report-summary-week-label\"><h5 class=\"total_week_month\">Итого часов<br>за неделю</h5></td>";

      $norm = $week_day_cnt * $day_norm;

      for ( $i = 0; $i < count($ids); $i++ ){    
	      $hour_min = format_time_hour_min( $week_dur[$i] );
 
        $hour = substr( $hour_min, 0, 2 );

	      $dur_differ = format_time_differs_from_norm_hour_min( $week_dur[$i], $norm ); 

        if ( (int)$hour >= $norm )
          echo "<td class=\"report-summary-cell report-summary-week-over\"><h5 class=\"total_week_month\">".$hour_min."<br>норма = ".$norm." ч. <br>Переработка = $dur_differ</h5>";
        else if ( (int)$hour < $norm )
          echo "<td class=\"report-summary-cell report-summary-week-under\"><h5 class=\"total_week_month\">".$hour_min."<br>норма = ".$norm." ч. <br>Недоработка = $dur_differ</h5>";

         
        echo "</td>";

        $week_dur[$i] = 0;
      }  	
      $week_day_cnt = 0;
       
      echo "</tr>";
    }
 
    if ( $month_day == 1 ){
      echo "<tr>";
      echo "<td class=\"report-summary-cell report-summary-month-label\"><h5 class=\"total_week_month\">Итого часов<br>за месяц</h5></td>";

      $m_norm = $month_day_cnt * $day_norm;
      
      for ( $i = 0; $i < count($ids); $i++ ){
	      $m_hour_min = format_time_hour_min( $month_dur[$i] );
 
        $m_hour = substr( $m_hour_min, 0, 3 );

	      $m_dur_differ = format_time_differs_from_norm_hour_min( $month_dur[$i], $m_norm ); 

        if ( (int)$m_hour >= $m_norm )
          echo "<td class=\"report-summary-cell report-summary-month-over\"><h5 class=\"total_week_month\">".$m_hour_min."<br>норма месяца = ".$m_norm." ч. <br>Переработка = $m_dur_differ</h5>";
        else if ( (int)$m_hour < $m_norm )
          echo "<td class=\"report-summary-cell report-summary-month-under\"><h5 class=\"total_week_month\">".$m_hour_min."<br>норма месяца = ".$m_norm." ч. <br>Недоработка = $m_dur_differ</h5>";

        $month_dur[$i] = 0;
      }  	
      $month_day_cnt = 0;
       
      echo "</tr>";
    }

    $week_day_name = GetWeekDayName( $week_day );
    
    echo "<tr>";

    echo "<td>";
    echo "<h5 class=\"fio\">".$date_one."<br>( ".$week_day_name." )</h5>";
    echo "</td>";

    for ( $i = 0; $i < count($ids); $i++ ){
      if ( strtotime( $date_one ) <= $now_date_time ){
        $query3 = db_query(
          $link,
          "SELECT in_time, out_time, eat_start, eat_stop, state FROM visiting where date = ? and user_id = ?",
          'si',
          array($date_one, (int)$ids[$i])
        );
        $merr=mysqli_error($link);
        if ( !$query3 ) {
          echo database_error_message($link, __FILE__ . ':' . __LINE__);
        }
        else{
          $vn3=mysqli_num_rows($query3);
        
          if ( $vn3 == 0 )
            $color_code = -1;

          $row3 = mysqli_fetch_array($query3, MYSQLI_ASSOC);
          if (!$row3) {
            $row3 = array(
              "in_time" => "00:00:00",
              "out_time" => "00:00:00",
              "eat_start" => "00:00:00",
              "eat_stop" => "00:00:00",
              "state" => 0,
            );
          }

          $in_time = $row3["in_time"];
          $out_time = $row3["out_time"];
          $eat_start = $row3["eat_start"];
          $eat_stop = $row3["eat_stop"]; 
	        $state = $row3["state"];
 
          $miss_rec = 0;
 
          if ( $now_date_time != strtotime( $date_one ) ){
            if ( $in_time == '00:00:00' AND $out_time == '00:00:00' ){
              $color_code = -1;
            }
	          else if ( $in_time == "00:00:00" ){
              $color_code = -1;
            }
            else if ( $out_time == "00:00:00" ){
              $color_code = -1;
            }
          
            if ( $eat_start == '00:00:00' AND $eat_stop == '00:00:00' ){ 
              $color_code = -1;            
            } 
            else if ( $eat_start == "00:00:00" ){
              $color_code = -1;
            }
            else if ( $eat_stop == "00:00:00" ){
              $color_code = -1;
            }
          } 
          else
            $color_code = 0;

          if ( $state == 0 ){  
	          $work_day_duration = format_time_( strtotime($out_time) - strtotime($in_time) - ( strtotime($eat_stop) - strtotime($eat_start) ) );
  	        $work_day_duration_time = strtotime($out_time) - strtotime($in_time) - ( strtotime($eat_stop) - strtotime($eat_start) );
	          $work_eat_duration = format_time_( strtotime($eat_stop) - strtotime($eat_start) );

            $week_dur[$i] += $work_day_duration_time;
            $month_dur[$i] += $work_day_duration_time;
          }
          else{ 
	          if ( strtotime($in_time) != "00:00:00"  AND $now_date_time == strtotime( $date_one ) )         if ( $color_code == 0 ){
	            if ( $eat_start == "00:00:00" AND $eat_stop == "00:00:00" ){
                $work_day_duration_time = strtotime( date("H:i:s") ) - strtotime( $in_time );
                $work_eat_duration = "00:00:00";
              }
              else if ( $eat_start != "00:00:00" AND $eat_stop != "00:00:00" ){  
                $work_day_duration_time = strtotime( date("H:i:s") ) - strtotime( $in_time ) - ( strtotime($eat_stop) - strtotime($eat_start) );
                $work_eat_duration = format_time_( strtotime($eat_stop) - strtotime($eat_start) );

              }
              else if ( $eat_start != "00:00:00" AND $eat_stop == "00:00:00" ){
                $work_day_duration_time = strtotime( $eat_start ) - strtotime( $in_time );
                $work_eat_duration = "??:??:??";
              } 
              $work_day_duration = format_time_( $work_day_duration_time );

	            $week_dur[$i] += $work_day_duration_time;
              $month_dur[$i] += $work_day_duration_time;
            }
	          else{
              $work_day_duration = "??:??:??";   
              $work_eat_duration = "??:??:??";   
            }
          }
                         
	  if ( $now_date_time != strtotime( $date_one ) ){
      if ( $color_code == 0 ){
        echo "<td class=\"report-day-worked\" >";

	      echo "<h5 class=\"info\">";

        echo "Раб. вр. с ".$in_time." до ".$out_time."<br>";
        echo "Обед с ".$eat_start." до ".$eat_stop." (".$work_eat_duration.")";

        echo "<br><br>Раб. вр. - обед: ".$work_day_duration;
	    }
	    else{  
          if ( $week_day == 6 OR $week_day == 0 )
	          $is_day_off = 1;
          else
	          $is_day_off = 0;

	      for ( $j=0; $j< count( $work_day ); $j++ ){
          if ( $date_one == $work_day[$j] ){
            $is_day_off = 0;
            break;
          }
      }

	    for ( $j=0; $j< count( $day_off ); $j++ ){
        if ( $date_one == $day_off[$j] ){
          $is_day_off = 1;
          break;
        }
      }

      if ( $is_day_off == 1 ){
        echo "<td class=\"report-day-off\" align = \"center\" valign = \"middle\">";

        echo "<h5 class=\"lite\">";
        echo "выходной";
      }
      else{
        echo "<td class=\"report-day-missing\" align = \"center\" valign = \"middle\">";
        echo "<h5 class=\"alarm\">";
        echo "Недостаточно<br>сведений!";                
      }
	  }
  }
  else{
    if ( isset( $state ) AND $state == 0 )
	    echo "<td class=\"report-day-worked\" valign = \"middle\" >";
	  else
      echo "<td class=\"report-day-off\" valign = \"middle\" >";

	    echo "<h5 class=\"info\">";

    if ( isset( $state ) ){
		  if ( $state == 4 ){
        $out_time = "??:??:??";
      }
		if ( $state == 3 ){
      $out_time = "??:??:??";
      $eat_stop = "??:??:??";
    }
		if ( $state == 2 ){
      $out_time = "??:??:??";
      $eat_start = "??:??:??";
      $eat_stop = "??:??:??";
    }
  }
  else{
    $in_time = "??:??:??";
    $out_time = "??:??:??";
    $eat_start = "??:??:??";
    $eat_stop = "??:??:??";
		$state = -1;
  }
  echo "Текущий рабочий день:<br>";
	echo "Раб. вр. с ";
  if ( $in_time != "??:??:??" )
    echo $in_time;
	else{
    echo "<font color=\"#ff0000\">??:??:??</font>";
  }

  echo " до ";

	if ( $out_time != "??:??:??" )
    echo $out_time."<br>";
  else{
    echo "<font color=\"#ff0000\">??:??:??</font><br>";
  }

  echo "Обед с ";
	if ( $eat_start != "??:??:??" )
    echo $eat_start;
	else{
    echo "<font color=\"#ff0000\">??:??:??</font>";
  }
              
  echo " до ";
	if ( $eat_stop != "??:??:??" )
    echo $eat_stop;
	else{
    echo "<font color=\"#ff0000\">??:??:??</font>";
  }

  if ( strtotime( $work_eat_duration ) > 0 AND $work_eat_duration != "00:00:00" )
    echo " (".$work_eat_duration.")";
	else{
    echo "<font color=\"#ff0000\"> (??:??:??)</font>";
  }
    
  if ( $state == 0 )
		echo "<br><br>День закрыт. Раб. вр. - обед: ".$work_day_duration; 

  if ( $state != 0 ){
		if ( $work_day_duration_time != 0 ) 
		  echo "<br><br>Раб. вр. - обед: ".$work_day_duration; 
		  if ( $work_day_duration_time == 0 ) {
        echo "<br><br>Раб. вр. - обед: ";
        echo "<font color=\"#ff0000\"> (??:??:??)</font>";
      }
    }
  }
  echo "</h5></td>";

  $color_code = 0;    
  }
}
else{
  if ( $week_day == 6 OR $week_day == 0 )
	  $is_day_off = 1;
    else
	  $is_day_off = 0;

	for ( $j=0; $j< count( $work_day ); $j++ ){
    if ( $date_one == $work_day[$j] ){
      $is_day_off = 0;
      break;
    }
  }
        
  for ( $j=0; $j< count( $day_off ); $j++ ){
    if ( $date_one == $day_off[$j] ){
      $is_day_off = 1;
      break;
    }
  }

  if ( $is_day_off == 1 ){ 
    echo "<td class=\"report-day-muted-off\" align = \"center\" valign = \"middle\">";
    echo "<h5 class=\"lite\">";
    echo "выходной</td>";
  }
	else
	  echo "<td></td>";
      }
    }
    echo "</tr>";
  }
  echo "</table>";
?>

</div>
</body>
</html>
