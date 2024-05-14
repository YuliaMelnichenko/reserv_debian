<script type="text/javascript" charset="utf-8"> 
function log_out()
{
  var perform=confirm('Выйти из системы?')
  if ( perform == true )
  {
    unset_cookie();
    location.href='exit.php';   
  }
}

function show_alerts()
{
  location.href='alerts.php';   
}

</script>

<?
session_start();

include_once "funcs.php";

$needToShow = 1;

if ( $_SESSION['ss_id'] == 500 || $_SESSION['ss_id'] == 501 )
{
  $needToShow = 0;
}







//if ( $_SESSION['ss_id'] != -1 && $_SESSION['ss_id'] != 500 && $_SESSION['ss_id'] != 501 )
{




  if ( $needToShow == 0 ){ echo "<font size=\"2\" color=\"#000000\" face=\"Arial\">Режим доступа: директор</font>"; }

  $dtvalStr = get_current_datetime_in_timezone_str( 1, 0 );
  echo "<table cellpadding=\"5\" cellspacing=\"0\" border=1>";
    echo "<tr>";
      echo "<td style=\"margin:0; padding:0;\" height = 1>";
    echo "</tr>";
    echo "<tr>";
      echo "</td>";
      echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"center\" width = 225>";
        echo "<div id=\"dateTimeFieldNav\">";
          echo "<h1 class=\"clock\">".$dtvalStr."</h1>";
        echo "</div>";
      echo "</td>";
    echo "</tr>";
  echo "</table>";

  echo "<table cellpadding=\"5\" cellspacing=\"0\" border=0 width = 190>";
  echo "<tr><td style=\"margin:0; padding:0; margin-left:0;\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 160>";
  echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0>";
    echo "<tr>";
      echo "<td style=\"margin:0; padding:0; margin-left:0;\" width = 205>";
        echo "<a class=\"nounder\" href=\"index.php\">";
          echo "<font size=\"5\" color=\"#ffffff\" face=\"Arial Black\">В</font>";
          echo "<font size=\"5\" color=\"#4a6e97\" face=\"Arial Black\"> ТОРИ 2.0</font>";
          echo "<font size=\"1\" color=\"#4a6e97\" face=\"Arial Black\"> beta</font>";
        echo "</a>";
      echo "</td>";
      echo "<td width=30 align=\"right\" style=\"margin:0; padding:0; margin-left:0;\">";
        echo "<img style=\"text-align: left; width:22px; height:22px;\"  title=\"Выйти из системы\" onclick=\"log_out();\" src=\"img/logout2.png\">";
      echo "</td>";
    echo "</tr>";
  echo "</table>";

  echo "</td></tr>";

  
  echo "<tr align=\"left\" ><td bgcolor=\"#ddeeff\" valign=\"top\" align=\"left\" width = 370>";

  if ( $needToShow == 1 ){ echo "<tr height=50 valign=\"bottom\"><td><button style=\" cursor: pointer; font-size: 80%; text-align: left; padding: 5px 27px 5px 5px; width:230px; height:40px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"location.href='index.php'\"><h5 class=\"bigger\">Текущий день</h5></button></td></tr>"; }
                           echo "<tr height=50 valign=\"bottom\"><td><button style=\"cursor: pointer; font-size: 80%; text-align: left; padding: 5px 5px 5px 5px; width:230px; height:40px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"location.href='my_report.php'\"><h5 class=\"bigger\">Временной отчет</h5></button></td></tr>";
  if ( $needToShow == 1 ){ echo "<tr><td><button style=\"cursor: pointer; font-size: 80%; text-align: left; padding: 5px 0px 5px 5px; width:230px; height:40px; background-color:#a8fd88; border:1px solid #888888;\" onclick=\"location.href='time_add.php'\"><h5 class=\"bigger\">Работа вне офиса</h5></button></td></tr>"; }
  if ( $needToShow == 1 ){ echo "<tr><td><button style=\"cursor: pointer; font-size: 80%; text-align: left; padding: 5px 0px 5px 5px; width:230px; height:40px; background-color:#a8fd88; border:1px solid #888888;\" onclick=\"location.href='delay.php'\"><h5 class=\"bigger\">Опоздания</h5></button></td></tr>"; }
  if ( $needToShow == 1 ){ echo "<tr><td><button style=\"cursor: pointer; font-size: 80%; text-align: left; padding: 5px 0px 5px 5px; width:230px; height:40px; background-color:#a8fd88; border:1px solid #888888;\" onclick=\"location.href='pause.php'\"><h5 class=\"bigger\">Приостановки учета времени</h5></button></td></tr>"; }
  echo "<tr><td height=8px></td></tr>";
  
  if ( am_i_superuser( $_SESSION['ss_id'] ) == 1 )
  {           
    $sv_id = $_SESSION['ss_id'];

    $notifCount = get_notification_count( $sv_id );
    $delayNotifCount = get_delay_notification_count( $sv_id );
    
    $counterStr = "";
    $delayNotifCountStr = "";

    if ( $notifCount > 0 ){ $counterStr = "($notifCount)"; }
    if ( $delayNotifCount > 0 ){ $delayNotifCountStr = "($delayNotifCount)"; }

    echo "<tr><td height = 30px valign = \"bottom\"><h5 class=\"bigger\">Уведомления:</h5></td></tr>";
    echo "<tr><td><button id=\"notifBtn\" style=\"cursor: pointer; font-size: 80%; text-align: left; padding: 5px 20px 5px 5px; width:230px; height:60px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"add_time_set_start(); location.href='time_approvement.php'\"><h5 class=\"biggersmall\">По работе вне офиса $counterStr</h5></button></td></tr>";
    echo "<tr><td><button id=\"notifDelayBtn\" style=\"cursor: pointer; font-size: 70%; text-align: left; padding: 5px 5px 5px 5px; width:230px; height:60px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"delay_set_start(); location.href='delay_approvement.php'\"><h5 class=\"biggersmall\">По опозданиям $delayNotifCountStr</h5></button></td></tr>";
    if ( $needToShow == 1 ){ echo "<tr><td><button id=\"notifPauseBtn\" style=\"cursor: pointer; font-size: 70%; text-align: left; padding: 5px 5px 5px 5px; width:230px; height:60px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"pause_set_start(); location.href='pause_view.php'\"><h5 class=\"biggersmall\">По приостановкам учета времени $pauseNotifCountStr</h5></button></td></tr>"; }
  } 
  echo "<tr><td height=8px></td></tr>";

  echo "<tr><td><button style=\"cursor: pointer; text-align: left; padding: 5px 25px 5px 10px; width:230px; height:40px; background-color:#f79398; border:1px solid #888888;\" onclick=\"window.open('http://192.168.100.17/my') \"><h5 class=\"bigger\">RedMine</h5></button></td></tr>";
  echo "<tr><td height = 3px></td></tr>";
  
  echo "</table>";













}



/*
if ( $_SESSION['ss_id'] == 500 )
{

  echo "<font size=\"2\" color=\"#000000\" face=\"Arial\">Режим доступа: директор</font>";

  echo "<table cellpadding=\"5\" cellspacing=\"0\" border=0 width = 190>";
  echo "<tr><td style=\"margin:0; padding:0; margin-left:0;\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 160>";
  echo "<table cellpadding=\"0\" cellspacing=\"0\" border=0><tr>";
  echo "<td style=\"margin:0; padding:0; margin-left:0;\" width = 160>";
    echo "<a class=\"nounder\" href=\"index.php\">";
      echo "<font size=\"5\" color=\"#ffffff\" face=\"Arial Black\">В</font>";
      echo "<font size=\"5\" color=\"#4a6e97\" face=\"Arial Black\"> ТОРИ</font>";
      echo "<font size=\"1\" color=\"#4a6e97\" face=\"Arial Black\"> beta</font>";
    echo "</a>";
  echo "</td>";

  echo "<td width=30 align=\"right\" style=\"margin:0; padding:0; margin-left:0;\">";

  echo "</td>";
  echo "</tr></table>";
  echo "</td></tr>";

  
  echo "<tr align=\"left\" ><td bgcolor=\"#ddeeff\" valign=\"top\" align=\"left\" width = 150>";

  echo "<tr height=40 valign=\"bottom\"><td><button style=\"font-size: 80%; text-align: left; padding: 5px 5px 5px 5px; width:190px; height:30px; background-color:#f8d888; border:1px solid #888888;\" onclick=\"location.href='my_report.php'\"><h5>Временной отчет</h5></button></td></tr>";
  echo "<tr><td height=8px></td></tr>";
  echo "<tr><td height=8px></td></tr>";

  echo "<tr><td><button style=\"text-align: left; padding: 5px 25px 5px 10px; width:190px; height:30px; background-color:#f79398; border:1px solid #888888;\" onclick=\"window.open('http://192.168.100.17/redmine') \"><h5>Переход в RedMine</h5></button></td></tr>";
  
  echo "</table>";
      echo "<br><br><br><br><br><br><br>";	

} */

?>
