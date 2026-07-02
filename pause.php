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
<script type="text/javascript" src="js/tory.js"></script> 

<?php
////////////////////////////////////////////////////////
include_once __DIR__ . "/funcs.php";
save_last_location( "time_add.php" );
auth();
////////////////////////////////////////////////////////

echo "<div id=\"delay_explanation_head\">";
echo "</div>"; 

echo "<div align=\"left\">";

echo "<table border=0>";
echo "<tr>";

echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 250>";

include_once __DIR__ . "/navigate.php";

echo "</td>";    
   
$wholeWidth = 700;

echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = $wholeWidth>";

echo "<h5 class=\"dark\"><br>/приостановки учета времени<br><br></h5>";

echo "<div id=\"pause_times_table\">";
echo "</div>"; 
echo "</td>"; 
echo "</tr>";    
echo "</table>";    
echo "</tr>";
echo "</table>";
echo "</div>";
?>

<script type="text/javascript" src="lib/jquery/jquery.js"></script> 
<script type="text/javascript" charset="utf-8"> 

show_pause_table();                                                                           

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

var timerId=setInterval( "update_clock()", 1000 );
</script> 

<?php
echo "</body>";
echo "</html>";  
?>