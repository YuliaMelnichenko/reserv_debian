<?php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/access.php';
require_ajax_auth();
header("Content-type: text/plain; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

include_once __DIR__ . "/../funcs.php";
include_once __DIR__ . "/../php_tori/connect.php";

$userID = (int)$_SESSION['ss_id'];

echo "<table class=\"journal-entry-layout\">";
echo "<tr>";

echo "<td class=\"journal-entry-toolbar-cell\">";
echo "<button class=\"journal-action-button journal-action-button-add\" onclick=\"as_add_time();\">Добавить время</button><br>";
echo "</td>";    
echo "</tr>";    
echo "<tr>";    

echo "<td class=\"journal-entry-content-cell\">";
echo "<div class=\"notification-table-scroll notification-table-scroll-full\">";
echo "<table class=\"add_time journal-entry-table\">";
echo "<tr class=\"journal-entry-head\">";

echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Начало<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Окончание<br>(дата, время)</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Длительность</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Основание</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Комментарий работника</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Лицо, принявшее<br>решение</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Комментарий лица,<br>принявшего решение</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Статус</h5>"."</td>";
echo "<td class=\"add_time journal-entry-head-cell\">"."<h5>Управление</h5>"."</td>";
echo "</tr>";
  
$colorMode = 1;
$color1 = "#ddffff";
$color3 = "#ffffff";

$addTimeInfo = get_all_add_work_info_by_user( $userID, 0 );

for ( $idx = 0; $idx < count( $addTimeInfo ); $idx ++ )
{
  $addInf = $addTimeInfo[$idx];

  $ta_id = (int)$addInf[8];
  $ta_start_dt = $addInf[0];
  $ta_stop_dt = $addInf[1];
  $ta_duration = $addInf[6];

  $ta_reason_description = $addInf[11];
  $ta_description = $addInf[3];
  $ta_SUdescription = $addInf[10];
  $ta_approved = $addInf[4];
  $ta_suir = $addInf[5];
  $pauseMode = $addInf[7];

  if ( $pauseMode == 1 ){ continue; }    
  if ( $ta_approved == 99 OR $ta_approved == 100 OR $ta_approved == 101 ){ continue; }    

  $superUserName = get_superuser_name_by_id( $ta_suir );

  if ( $ta_approved == 0 )
  { 
    $approvedStr = journal_status_label("на рассмотрении");
  }
  else if ( $ta_approved == -1 )
  { 
    $approvedStr = journal_status_label("отклонено");
  }
  else if ( $ta_approved == 1 )
  { 
    $approvedStr = journal_status_label("принято");
  }   

  $time_duration = $ta_duration > 0 ? format_time_( $ta_duration ) : "";
  	
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
  $buttonAdd2 = "onclick=\"ta_delete('$ta_id');\"";
  $buttonAdd3 = "title=\"удалить запись\"";

  $statusClass = "";

  if ( $ta_approved == -1 )
  {
    $buttonAdd1 = "disabled";
    $statusClass = "journal-entry-status-refused";
    $buttonAdd2 = "onclick=\"alert( 'запись уже заквитирована. Удаление невозможно');\"";
    $buttonAdd3 = "title=\"запись уже заквитирована. Удаление невозможно\"";
  }
  if ( $ta_approved == 1 )
  {
    $buttonAdd1 = "disabled";
    $statusClass = "journal-entry-status-accepted";
    $buttonAdd2 = "onclick=\"alert( 'запись уже заквитирована. Удаление невозможно');\"";
    $buttonAdd3 = "title=\"запись уже заквитирована. Удаление невозможно\"";
  }

  $rowClass = $color == $color1 ? "journal-entry-row-alt" : "journal-entry-row";

  echo "<tr class=\"$rowClass\">";
echo "<td class=\"add_time journal-entry-date-cell\"><h5 class=\"small\">" . html_escape($ta_start_dt) . "</h5></td>";
echo "<td class=\"add_time journal-entry-date-cell\"><h5 class=\"small\">" . html_escape($ta_stop_dt) . "</h5></td>";
  echo "<td class=\"add_time journal-entry-duration-cell\"><h5 class=\"small\">".$time_duration."</h5></td>";
echo "<td class=\"add_time journal-entry-reason-cell\"><h5 class=\"small\">" . html_escape($ta_reason_description) . "</h5></td>";
echo "<td class=\"add_time journal-entry-comment-cell\"><h5 class=\"small\">" . html_escape($ta_description) . "</h5></td>";

echo "<td class=\"add_time journal-entry-supervisor-cell\"><h5 class=\"small\">" . html_escape($superUserName) . "</h5></td>";
echo "<td class=\"add_time journal-entry-comment-cell\"><h5 class=\"small\">" . html_escape($ta_SUdescription) . "</h5></td>";
  echo "<td class=\"add_time journal-entry-status-cell $statusClass\">$approvedStr</td>";
  echo "<td class=\"add_time journal-entry-actions-cell\">";
    echo "<button class=\"journal-action-button journal-action-button-delete\" $buttonAdd1 $buttonAdd2 $buttonAdd3 name=\"nextBtn\">Удалить</button>";
  echo "</td>";
  echo "</tr>";
}

echo "</table>";
echo "</div>";
echo "</td>";
echo "</tr>";
echo "</table>";
?>
