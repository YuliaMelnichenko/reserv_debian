<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
require_once __DIR__ . '/inc/access.php';
include_once __DIR__ . "/funcs.php";
save_last_location( "entrance_management.php" );
require_page_superuser();
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
<script type="text/javascript" charset="utf-8"> 
</script>

<?php
echo "<div align=\"left\">";

include_once __DIR__ . "/php_tori/connect.php";

mysqli_set_charset($link, "utf8");

echo "<table border=1>";
  echo "<tr>";
    echo "<td bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 120>";
      include_once __DIR__ . "/navigate.php";
    echo "</td>";    
    echo "<td class=\"nopadding_s\" bgcolor=\"#ddeeff\" bordercolor=\"#888888\" valign=\"top\" align=\"left\" width = 120>";
      echo "<h5 class=\"dark\">/управление временем прихода сотрудников</h5>";
      echo "<div id=\"entrance_approvement\">";
      echo "</div>"; 
    echo "</td>"; 
  echo "</tr>";
echo "</table>";
echo "</div>";
?>

<script type="text/javascript" src="js/tory.js"></script> 
<script type="text/javascript" charset="utf-8"> 

show_entrance_page();
</script> 

<?php
echo "</body>";
echo "</html>";  
?>
