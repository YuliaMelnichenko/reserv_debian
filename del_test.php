<?php
ob_start();
require_once __DIR__ . '/inc/session.php';
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
<script type="text/javascript" charset="utf-8"> 

</script>

<?php
include_once __DIR__ . "/funcs.php";

$notificationCount = 0;
$acceptedNotificationCount = 0;
$refusedNotificationCount = 0;
$deletedNotificationCount = 0;
$newNotificationCount = 0;
get_delay_notif_counts( 1, $notificationCount, $acceptedNotificationCount, $refusedNotificationCount, $deletedNotificationCount, $newNotificationCount );

$summ = $acceptedNotificationCount + $refusedNotificationCount + $deletedNotificationCount + $newNotificationCount;

echo "notificationCount = $notificationCount <br>";
echo "acceptedNotificationCount = $acceptedNotificationCount <br>";
echo "refusedNotificationCount = $refusedNotificationCount <br>";
echo "deletedNotificationCount = $deletedNotificationCount <br>";
echo "newNotificationCount = $newNotificationCount <br>";
echo "summ = $summ <br>";
echo "</body>";
echo "</html>";  
?>