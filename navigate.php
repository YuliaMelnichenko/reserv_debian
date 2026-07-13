<script type="text/javascript" src="lib/jquery/jquery.js"></script>
<script type="text/javascript" charset="utf-8"> 

function log_out(){
  let perform = confirm('Выйти из системы?')
  if ( perform == true ){
    unset_cookie();
    location.href='exit.php';   
  }
}

</script>

<?php
include_once __DIR__ . "/funcs.php";
require_once __DIR__ . '/inc/access.php';

$needToShow = 1;

if ( $_SESSION['ss_id'] == 500 || $_SESSION['ss_id'] == 501 ){
  $needToShow = 0;
}

{
  if ( $needToShow == 0 ){ echo "<span class=\"nav-access-mode\">Режим доступа: директор</span>"; }

  $dtvalStr = get_current_datetime_in_timezone_str( 1, 0 );
  echo "<table class=\"nav-clock-table\">";
    echo "<tr>";
      echo "<td class=\"nav-zero-cell\"></td>";
    echo "</tr>";
    echo "<tr>";
      echo "<td class=\"nav-clock-cell\">";
        echo "<div id=\"dateTimeFieldNav\">";
          echo "<h1 class=\"clock\">".$dtvalStr."</h1>";
        echo "</div>";
      echo "</td>";
    echo "</tr>";
  echo "</table>";

  echo "";

  echo "<table class=\"nav-panel-table\">";
  echo "<tr><td class=\"nav-panel-cell\">";
  echo "<table class=\"nav-brand-table\">";
    echo "<tr>";
      echo "<td class=\"nav-brand-cell\">";
        echo "<a class=\"nounder\" href=\"index.php\">";
          echo "<p class=\"nav-brand-title\"><span class=\"nav-brand-prefix\"> В </span><span class=\"nav-brand-name\"> ТОРИ 3.0 </span></p>";
        echo "</a>";
      echo "</td>";
      echo "<td class=\"nav-icon-cell\">";
        echo "<img class=\"nav-icon\" title=\"Что нового?\" onclick=\"show_information();\" src=\"img/news.png\">";
      echo "</td>";
      echo "<td class=\"nav-icon-cell\">";
        echo "<img class=\"nav-icon\" title=\"Выйти из системы\" onclick=\"log_out();\" src=\"img/logout3.png\">";
      echo "</td>";
    echo "</tr>";
  echo "</table>";

  echo "</td></tr>";
  
  echo "<tr><td class=\"nav-menu-cell\">";
  echo "<table class=\"nav-menu-list\">";

  if ( $needToShow == 1 ){ echo "<tr height=50 valign=\"bottom\"><td><button class=\"nav-menu-button nav-menu-button-main nav-menu-button-current\" onclick=\"location.href='index.php'\"><h5 class=\"bigger\">Текущий день</h5></button></td></tr>"; }
                           echo "<tr height=50 valign=\"bottom\"><td><button class=\"nav-menu-button nav-menu-button-main\" onclick=\"location.href='my_report.php'\"><h5 class=\"bigger\">Временной отчет</h5></button></td></tr>";
  if ( $needToShow == 1 ){ echo "<tr><td><button class=\"nav-menu-button nav-menu-button-section\" onclick=\"location.href='time_add.php'\"><h5 class=\"bigger\">Работа вне офиса</h5></button></td></tr>"; }
  if ( $needToShow == 1 ){ echo "<tr><td><button class=\"nav-menu-button nav-menu-button-section\" onclick=\"location.href='delay.php'\"><h5 class=\"bigger\">Опоздания</h5></button></td></tr>"; }
  if ( $needToShow == 1 ){ echo "<tr><td><button class=\"nav-menu-button nav-menu-button-section\" onclick=\"location.href='pause.php'\"><h5 class=\"bigger\">Приостановки учета времени</h5></button></td></tr>"; }
  if ( $needToShow == 1 ){ echo "<tr><td><button class=\"nav-menu-button nav-menu-button-section\" onclick=\"location.href='sport_pause.php'\"><h5 class=\"bigger\">Тренажерный зал</h5></button></td></tr>"; }
  if (access_current_user_can_manage_staff_leaves()) {
      if ( $needToShow == 1 ){ echo "<tr><td><button class=\"nav-menu-button nav-menu-button-section\" onclick=\"location.href='staff_leaves.php'\"><h5 class=\"bigger\">Отсутствие сотрудников</h5></button></td></tr>"; }
  }
  if (access_current_user_can_view_work_overtime()) {
      if ( $needToShow == 1 ){ echo "<tr><td><button class=\"nav-menu-button nav-menu-button-section\" onclick=\"location.href='work_overtime.php'\"><h5 class=\"bigger\">Переработки</h5></button></td></tr>"; }
  }

  $accountingErrorsCount = 0;

  if (!isset($link) || !$link) {
    include __DIR__ . "/php_tori/connect.php";
  }

  if (isset($link) && $link) {
    $accountingErrorsSyncDate = isset($_SESSION['accounting_errors_sync_date'])
      ? (string)$_SESSION['accounting_errors_sync_date']
      : '';

    if ($accountingErrorsSyncDate !== date('Y-m-d')) {
      $syncResult = sync_accounting_errors_for_user(
        $link,
        (int)$_SESSION['ss_id'],
        get_accounting_errors_default_depth_days()
      );

      if ($syncResult !== false) {
        $_SESSION['accounting_errors_sync_date'] = date('Y-m-d');
      }
    }

    $accountingErrorsCount = get_accounting_errors_count($link, (int)$_SESSION['ss_id']);
  }

  if ($needToShow == 1 && $accountingErrorsCount > 0) {
    echo "<tr><td><button class=\"nav-menu-button nav-menu-button-section\" onclick=\"location.href='accounting_errors.php'\"><h5 class=\"bigger\">Ошибки учета ($accountingErrorsCount)</h5></button></td></tr>";
  }

  if ( am_i_superuser( $_SESSION['ss_id'] ) == 1 ){
    $sv_id = $_SESSION['ss_id'];

    $notifCount = get_notification_count( $sv_id );
    $delayNotifCount = get_delay_notification_count( $sv_id );
    $accountingErrorsNotifCount = isset($link) && $link
      ? get_accounting_errors_notification_count($link, $sv_id)
      : 0;
    
    $counterStr = "";
    $delayNotifCountStr = "";
    $pauseNotifCountStr = "";
    $accountingErrorsNotifCountStr = "";

    if ( $notifCount > 0 ){ $counterStr = "($notifCount)"; }
    if ( $delayNotifCount > 0 ){ $delayNotifCountStr = "($delayNotifCount)"; }
    if ( $accountingErrorsNotifCount > 0 ){ $accountingErrorsNotifCountStr = "($accountingErrorsNotifCount)"; }

    echo "<tr><td height = 30px valign = \"bottom\"><h5 class=\"bigger\">Уведомления:</h5></td></tr>";
    echo "<tr><td><button id=\"notifBtn\" class=\"nav-menu-button nav-notification-button nav-notification-button-wide\" onclick=\"add_time_set_start(); location.href='time_approvement.php'\"><h5 class=\"biggersmall\">По работе вне офиса $counterStr</h5></button></td></tr>";
    echo "<tr><td><button id=\"notifDelayBtn\" class=\"nav-menu-button nav-notification-button\" onclick=\"delay_set_start(); location.href='delay_approvement.php'\"><h5 class=\"biggersmall\">По опозданиям $delayNotifCountStr</h5></button></td></tr>";
    if ( $needToShow == 1 ){ echo "<tr><td><button id=\"notifPauseBtn\" class=\"nav-menu-button nav-notification-button\" onclick=\"pause_set_start(); location.href='pause_view.php'\"><h5 class=\"biggersmall\">По приостановкам учета времени $pauseNotifCountStr</h5></button></td></tr>"; }
    echo "<tr><td><button id=\"notifAccountingErrorsBtn\" class=\"nav-menu-button nav-notification-button\" onclick=\"location.href='accounting_errors_approvement.php'\"><h5 class=\"biggersmall\">По ошибкам учета $accountingErrorsNotifCountStr</h5></button></td></tr>";
  } 
  echo "<tr><td class=\"nav-spacer-cell\" height=8px></td></tr>";

  echo "<tr><td><button class=\"nav-menu-button nav-redmine-button\" onclick=\"window.open('http://192.168.100.17/my') \"><h5 class=\"bigger\">RedMine</h5></button></td></tr>";
  echo "<tr><td height = 3px></td></tr>";

  echo "</table>";
  echo "</td></tr>";
  echo "</table>";
}
?>
